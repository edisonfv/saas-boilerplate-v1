<?php

namespace Modules\Signatures\Http\Controllers;

use App\Enums\SignaturePaymentMethod;
use App\Enums\SignaturePaymentStatus;
use App\Enums\SignatureRequestSource;
use App\Enums\SignatureRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\SignaturePayment;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestDocument;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Signatures\SignatureIssuance;
use App\Services\Signatures\SignatureLinks;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\SignatureProviderException;
use App\Services\Signatures\SignatureRequestManager;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\StoreSignatureRequestRequest;
use Modules\Signatures\Http\Requests\UpdateSignatureRequestRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Point of sale of electronic signatures inside a tenant workspace:
 * capture applications (or review the ones left on the storefront),
 * submit them to the certification authority — which consumes the
 * tenant's quota — and follow their status.
 */
class SignatureRequestController extends Controller
{
    public function __construct(
        private SignatureRequestManager $manager,
        private SignaturePresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $status = SignatureRequestStatus::tryFrom($request->string('status')->toString());

        $requests = SignatureRequest::query()
            ->search($request->string('search')->toString())
            ->when($status, fn ($query) => $query->where('status', $status?->value))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SignatureRequest $signatureRequest) => $this->presenter->requestSummary($signatureRequest));

        return Inertia::render('Signatures/Requests/Index', [
            'requests' => $requests,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $status?->value,
            ],
            'statuses' => SignatureRequestStatus::toArray(),
            'counts' => [
                'drafts' => SignatureRequest::query()->where('status', SignatureRequestStatus::Draft()->value)->count(),
                'issued' => SignatureRequest::query()->where('status', SignatureRequestStatus::Issued()->value)->count(),
                'payment_review' => SignatureRequest::query()->where('payment_status', SignaturePaymentStatus::UnderReview()->value)->count(),
            ],
            'account' => $this->presenter->account($this->tenant()->signatureAccount()->first()),
            'can' => [
                'create' => $user->can('tenant.signature-requests.create'),
                'storefront' => $user->can('tenant.signature-storefront.update'),
                'payments' => $user->can('tenant.signature-requests.payments'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Signatures/Requests/Create', [
            ...$this->formOptions(),
            'account' => $this->presenter->account($this->tenant()->signatureAccount()->first()),
        ]);
    }

    public function store(StoreSignatureRequestRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $signatureRequest = $this->manager->create(
            $request->product(),
            $request->validated(),
            $request->documents(),
            SignatureRequestSource::Workspace(),
            (string) $user->getKey(),
            $user->name,
        );

        return redirect()->route('tenant.signatures.requests.show', $signatureRequest)
            ->with('status', 'signature-request-created');
    }

    public function show(Request $request, SignatureRequest $signatureRequest, SignatureLinks $links): Response
    {
        $signatureRequest->load(['documents', 'events', 'payments' => fn ($query) => $query->latest()]);
        $user = $request->user();
        $canManagePayments = $user->can('tenant.signature-requests.payments');
        $paymentLink = $canManagePayments && $signatureRequest->payment_status->equals(SignaturePaymentStatus::Pending())
            ? $links->payment($signatureRequest)
            : null;

        return Inertia::render('Signatures/Requests/Show', [
            'request' => $this->presenter->requestDetail(
                $signatureRequest,
                fn (SignatureRequestDocument $document) => route('tenant.signatures.requests.documents.show', [$signatureRequest, $document]),
            ),
            'payments' => $signatureRequest->payments->map(fn (SignaturePayment $payment) => [
                'id' => $payment->id,
                'method' => $payment->method->value,
                'method_label' => $payment->method->label,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
                'review' => $payment->review->value,
                'review_label' => $payment->review->label,
                'rejection_reason' => $payment->rejection_reason,
                'reported_by_name' => $payment->reported_by_name,
                'reviewed_by_name' => $payment->reviewed_by_name,
                'reviewed_at' => $payment->reviewed_at,
                'created_at' => $payment->created_at,
                'receipt_url' => $payment->hasReceipt() && $canManagePayments
                    ? route('tenant.signatures.requests.payments.receipt', [$signatureRequest, $payment])
                    : null,
            ])->values(),
            'paymentLink' => $paymentLink,
            'paymentWhatsappUrl' => $paymentLink !== null ? $links->whatsappTo(
                $signatureRequest->mobile_phone,
                "Hola {$signatureRequest->first_names}, para continuar con tu firma electrónica {$signatureRequest->code()} realiza el pago y sube tu comprobante aquí: {$paymentLink}",
            ) : null,
            'paymentMethods' => SignaturePaymentMethod::toArray(),
            'account' => $this->presenter->account($this->tenant()->signatureAccount()->first()),
            'can' => [
                'update' => $user->can('tenant.signature-requests.update'),
                'delete' => $user->can('tenant.signature-requests.delete'),
                'submit' => $user->can('tenant.signature-requests.submit'),
                'payments' => $canManagePayments,
            ],
        ]);
    }

    public function edit(SignatureRequest $signatureRequest): Response|RedirectResponse
    {
        if (! $signatureRequest->status->isEditable()) {
            return redirect()->route('tenant.signatures.requests.show', $signatureRequest);
        }

        $signatureRequest->load('documents');

        return Inertia::render('Signatures/Requests/Edit', [
            ...$this->formOptions(),
            'request' => [
                'id' => $signatureRequest->id,
                'code' => $signatureRequest->code(),
                ...$this->presenter->requestForm($signatureRequest),
                'uploaded_documents' => $signatureRequest->documents
                    ->map(fn (SignatureRequestDocument $document) => $document->kind->value)
                    ->values(),
            ],
        ]);
    }

    public function update(UpdateSignatureRequestRequest $request, SignatureRequest $signatureRequest): RedirectResponse
    {
        $this->guard(fn () => $this->manager->update(
            $signatureRequest,
            $request->product(),
            $request->validated(),
            $request->documents(),
            $request->user()->name,
        ), 'first_names');

        return redirect()->route('tenant.signatures.requests.show', $signatureRequest)
            ->with('status', 'signature-request-updated');
    }

    public function destroy(SignatureRequest $signatureRequest): RedirectResponse
    {
        $this->guard(fn () => $this->manager->discard($signatureRequest), 'request');

        return redirect()->route('tenant.signatures.requests.index')->with('status', 'signature-request-deleted');
    }

    public function submit(Request $request, SignatureRequest $signatureRequest, SignatureIssuance $issuance): RedirectResponse
    {
        $signatureRequest->load('documents');

        $this->guard(
            fn () => $issuance->submit($this->tenant(), $signatureRequest, $request->user()->name),
            'submit',
        );

        return back()->with('status', 'signature-request-submitted');
    }

    public function document(SignatureRequest $signatureRequest, SignatureRequestDocument $document): StreamedResponse
    {
        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $storefront = SignatureStorefront::current();
        $products = SignatureProduct::query()->active()->orderBy('credit_unit_price')->get();

        return $this->presenter->formOptions(
            $products,
            $products->mapWithKeys(fn (SignatureProduct $product) => [$product->id => $storefront->priceFor($product->id)])->all(),
        );
    }

    /**
     * Run a domain action, turning business-rule failures into a
     * validation error on $field.
     */
    private function guard(callable $action, string $field): void
    {
        try {
            $action();
        } catch (DomainException|SignatureProviderException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }

    private function tenant(): Tenant
    {
        /** @var Tenant */
        return tenant();
    }
}
