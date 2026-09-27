<?php

namespace Modules\Signatures\Http\Controllers;

use App\Enums\SignatureApplicantType;
use App\Enums\SignatureDocumentKind;
use App\Enums\SignatureRequestSource;
use App\Http\Controllers\Controller;
use App\Models\SignatureProduct;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\SignatureRequestManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
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

    public function show(): Response
    {
        $storefront = SignatureStorefront::current();

        return Inertia::render('Signatures/Storefront/Show', [
            'storefront' => $this->storefrontProps($storefront),
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
        ]);
    }

    public function create(Request $request): Response
    {
        $storefront = SignatureStorefront::current();
        $products = $this->products();

        return Inertia::render('Signatures/Storefront/Apply', [
            'storefront' => $this->storefrontProps($storefront),
            'selectedProductId' => $products->firstWhere('id', $request->string('firma')->toString())?->id,
            ...$this->presenter->formOptions(
                $products,
                $products->mapWithKeys(fn (SignatureProduct $product) => [$product->id => $storefront->priceFor($product->id)])->all(),
            ),
        ]);
    }

    public function store(StoreStorefrontSignatureRequest $request, SignatureRequestManager $manager): RedirectResponse
    {
        $storefront = SignatureStorefront::current();
        $product = $request->product();

        $signatureRequest = $manager->create(
            $product,
            [...$request->validated(), 'sale_price' => $storefront->priceFor($product->id) ?? $product->suggested_retail_price],
            $request->documents(),
            SignatureRequestSource::Storefront(),
            actorName: trim($request->string('first_names').' '.$request->string('first_surname')),
        );

        return redirect()->route('tenant.signatures.storefront.received')
            ->with('signature_request_code', $signatureRequest->code())
            ->with('signature_request_product', $product->name);
    }

    public function received(Request $request): Response|RedirectResponse
    {
        $code = $request->session()->get('signature_request_code');

        if (! is_string($code)) {
            return redirect('/');
        }

        $storefront = SignatureStorefront::current();

        return Inertia::render('Signatures/Storefront/Received', [
            'storefront' => $this->storefrontProps($storefront),
            'code' => $code,
            'whatsappUrl' => $storefront->whatsappUrl(
                "Hola, acabo de enviar mi solicitud de firma electrónica {$code} ("
                .$request->session()->get('signature_request_product', 'firma electrónica')
                .'). Quisiera continuar con el pago y la validación.',
            ),
        ]);
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
