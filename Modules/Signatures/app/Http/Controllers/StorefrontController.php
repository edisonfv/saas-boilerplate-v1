<?php

namespace Modules\Signatures\Http\Controllers;

use App\Enums\SignatureApplicantType;
use App\Enums\SignatureDocumentKind;
use App\Enums\SignaturePaymentMethod;
use App\Enums\SignaturePaymentReview;
use App\Enums\SignaturePaymentStatus;
use App\Enums\SignatureRequestSource;
use App\Http\Controllers\Controller;
use App\Models\SignatureInvitation;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Services\Signatures\SignatureInvitationManager;
use App\Services\Signatures\SignatureLinks;
use App\Services\Signatures\SignaturePaymentManager;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\SignatureRequestManager;
use App\Services\Signatures\StorefrontMedia;
use App\Services\Signatures\StorefrontSeo;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\ReportPaymentReceiptRequest;
use Modules\Signatures\Http\Requests\StoreStorefrontSignatureRequest;

/**
 * The tenant's public website, delivered as part of the Signatures module:
 * the landing page at the root of its subdomain (see
 * App\Http\Controllers\HomeController) and the step-by-step application
 * flow at /solicitud. Applications land as drafts: an operator reviews
 * them, collects the payment and submits them from the workspace
 * (consuming the tenant's quota).
 */
class StorefrontController extends Controller
{
    public function __construct(private SignaturePresenter $presenter) {}

    public function show(StorefrontSeo $seo, StorefrontMedia $media): Response
    {
        $storefront = SignatureStorefront::current();
        $seoData = $seo->landing($storefront, $this->products());

        return Inertia::render('Signatures/Storefront/Show', [
            'storefront' => $this->storefrontProps($storefront),
            'pageTitle' => $seoData['title'],
            'slides' => $media->slides($storefront),
            'uses' => $storefront->resolvedUses(),
            'steps' => $storefront->resolvedSteps(),
            'faqs' => $storefront->resolvedFaqs(),
            'products' => $this->products()->map(fn (SignatureProduct $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'validity_label' => $product->validity->label,
                'container' => $product->container->value,
                'container_label' => $product->container->label,
                'price' => $storefront->priceFor($product->id) ?? $product->suggested_retail_price,
            ])->values(),
            'requirements' => collect(SignatureApplicantType::cases())->map(fn (SignatureApplicantType $type) => [
                'type' => $type->value,
                'label' => $type->label,
                'documents' => collect(SignatureDocumentKind::requiredFor($type))
                    ->map(fn (SignatureDocumentKind $kind) => $kind->label)
                    ->values(),
            ])->values(),
        ])->withViewData(['seo' => $seoData]);
    }

    public function create(Request $request, StorefrontSeo $seo): Response
    {
        $storefront = SignatureStorefront::current();
        $products = $this->products();
        $seoData = $seo->page(
            'Solicita tu firma electrónica en línea',
            'Completa tu solicitud de firma electrónica en minutos: elige tu firma, ingresa tus datos y sube tus documentos desde el celular.',
            '/solicitud',
        );

        return Inertia::render('Signatures/Storefront/Apply', [
            'pageTitle' => $seoData['title'],
            'storefront' => $this->storefrontProps($storefront),
            'selectedProductId' => $products->firstWhere('id', $request->string('firma')->toString())?->id,
            ...$this->presenter->formOptions(
                $products,
                $products->mapWithKeys(fn (SignatureProduct $product) => [$product->id => $storefront->priceFor($product->id)])->all(),
            ),
        ])->withViewData(['seo' => $seoData]);
    }

    public function store(
        StoreStorefrontSignatureRequest $request,
        SignatureRequestManager $manager,
        SignaturePaymentManager $payments,
    ): RedirectResponse {
        $storefront = SignatureStorefront::current();
        $product = $request->product();

        $signatureRequest = $manager->create(
            $product,
            [...$request->validated(), 'sale_price' => $storefront->priceFor($product->id) ?? $product->suggested_retail_price],
            $request->documents(),
            SignatureRequestSource::Storefront(),
            actorName: trim($request->string('first_names').' '.$request->string('first_surname')),
        );

        // Unpaid until the customer uploads a receipt and staff confirms it.
        $payments->sendPaymentLink($signatureRequest);

        return redirect()->route('tenant.signatures.storefront.received')
            ->with('signature_request_id', $signatureRequest->id);
    }

    public function received(Request $request, SignatureLinks $links, StorefrontSeo $seo): Response|RedirectResponse
    {
        $signatureRequest = SignatureRequest::query()->find($request->session()->get('signature_request_id'));

        if (! $signatureRequest instanceof SignatureRequest) {
            return redirect('/');
        }

        $storefront = SignatureStorefront::current();
        $code = $signatureRequest->code();

        return Inertia::render('Signatures/Storefront/Received', [
            'storefront' => $this->storefrontProps($storefront),
            'code' => $code,
            'isPaid' => $signatureRequest->isPaid(),
            'amount' => $signatureRequest->sale_price,
            'email' => $signatureRequest->email,
            'paymentUrl' => $signatureRequest->isPaid() ? null : $links->payment($signatureRequest),
            'whatsappUrl' => $storefront->whatsappUrl(
                "Hola, envié mi solicitud de firma electrónica {$code} ({$signatureRequest->product_name}). "
                .($signatureRequest->isPaid() ? 'Quisiera saber el estado de mi trámite.' : 'Quisiera coordinar el pago.'),
            ),
        ])->withViewData(['seo' => $seo->private('Solicitud recibida')]);
    }

    /**
     * The customer's signed payment link: bank accounts, amount and the
     * receipt upload while payment is pending; the status afterwards.
     */
    public function payment(Request $request, SignatureRequest $signatureRequest, StorefrontSeo $seo): Response
    {
        $storefront = SignatureStorefront::current();
        $rejected = $signatureRequest->payments()
            ->where('review', SignaturePaymentReview::Rejected()->value)
            ->latest()
            ->first();

        return Inertia::render('Signatures/Storefront/Payment', [
            'storefront' => $this->storefrontProps($storefront),
            'request' => [
                'code' => $signatureRequest->code(),
                'applicant_name' => $signatureRequest->applicantName(),
                'product_name' => $signatureRequest->product_name,
                'amount' => $signatureRequest->sale_price,
                'payment_status' => $signatureRequest->payment_status->value,
                'payment_status_label' => $signatureRequest->payment_status->label,
                'rejection_reason' => $signatureRequest->payment_status->equals(SignaturePaymentStatus::Pending())
                    ? $rejected?->rejection_reason
                    : null,
            ],
            'bankAccounts' => $storefront->bank_accounts ?? [],
            'methods' => collect(SignaturePaymentMethod::selfReported())
                ->mapWithKeys(fn (SignaturePaymentMethod $method) => [$method->value => $method->label]),
            // Posting back to the same signed URL keeps the signature valid.
            'action' => $request->getRequestUri(),
        ])->withViewData(['seo' => $seo->private('Pago de tu firma')]);
    }

    public function reportPayment(
        ReportPaymentReceiptRequest $request,
        SignatureRequest $signatureRequest,
        SignaturePaymentManager $payments,
    ): RedirectResponse {
        try {
            $payments->reportReceipt(
                $signatureRequest,
                SignaturePaymentMethod::from($request->string('method')->toString()),
                $request->input('reference'),
                $request->file('receipt'),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['receipt' => $exception->getMessage()]);
        }

        return back()->with('status', 'signature-receipt-received');
    }

    /**
     * Application form opened from a prepaid, single-use invitation.
     */
    public function invitation(Request $request, SignatureInvitation $invitation, StorefrontSeo $seo): Response
    {
        $storefront = SignatureStorefront::current();
        $products = $this->products()->where('id', $invitation->signature_product_id)->values();

        return Inertia::render('Signatures/Storefront/Apply', [
            'storefront' => $this->storefrontProps($storefront),
            'selectedProductId' => $invitation->signature_product_id,
            'invitation' => [
                'customer_name' => $invitation->customer_name,
                'product_name' => $invitation->product_name,
                'is_usable' => $invitation->isUsable(),
                // Posting back to the same signed URL keeps the signature valid.
                'action' => $request->getRequestUri(),
            ],
            ...$this->presenter->formOptions($products, [$invitation->signature_product_id => $invitation->amount]),
        ])->withViewData(['seo' => $seo->private('Completa tu solicitud')]);
    }

    public function redeemInvitation(
        StoreStorefrontSignatureRequest $request,
        SignatureInvitation $invitation,
        SignatureInvitationManager $invitations,
    ): RedirectResponse {
        try {
            $signatureRequest = $invitations->redeem($invitation, $request->validated(), $request->documents());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['invitation' => $exception->getMessage()]);
        }

        return redirect()->route('tenant.signatures.storefront.received')
            ->with('signature_request_id', $signatureRequest->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function storefrontProps(SignatureStorefront $storefront): array
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        return [
            'company_name' => $tenant->company_name ?? $tenant->getTenantKey(),
            'headline' => $storefront->headline,
            'description' => $storefront->description,
            'contact_email' => $storefront->contact_email,
            'contact_phone' => $storefront->contact_phone,
            'whatsapp_url' => $storefront->whatsappUrl(),
        ];
    }

    /**
     * @return Collection<int, SignatureProduct>
     */
    private function products(): Collection
    {
        return SignatureProduct::query()->active()->orderBy('credit_unit_price')->get()->toBase();
    }
}
