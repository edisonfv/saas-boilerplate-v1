<?php

namespace App\Services\Signatures;

use App\Enums\Gender;
use App\Enums\IdentityDocumentType;
use App\Enums\SignatureApplicantType;
use App\Enums\SignatureDocumentKind;
use App\Models\SignatureAccount;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestDocument;
use App\Models\SignatureRequestEvent;
use Closure;
use Illuminate\Support\Collection;

/**
 * Shapes signature data for Inertia pages (central console and tenant
 * workspace), exposing enum labels explicitly next to their values.
 */
class SignaturePresenter
{
    public function __construct(private SignatureWallet $wallet) {}

    /**
     * @return array<string, mixed>|null
     */
    public function account(?SignatureAccount $account): ?array
    {
        if ($account === null) {
            return null;
        }

        $products = SignatureProduct::query()->active()->orderBy('credit_unit_price')->get();
        $balances = $account->balances()->pluck('available_units', 'signature_product_id');

        return [
            'id' => $account->id,
            'affiliation_mode' => $account->affiliation_mode->value,
            'affiliation_mode_label' => $account->affiliation_mode->label,
            'is_credit' => $account->isCredit(),
            'is_active' => $account->is_active,
            'credit_limit' => $account->credit_limit,
            'credit_used' => $account->credit_used,
            'credit_available' => Money::fromCents($account->availableCreditCents()),
            'notes' => $account->notes,
            'products' => $products->map(fn (SignatureProduct $product) => [
                ...$this->product($product),
                'available_units' => (int) ($balances[$product->id] ?? 0),
                'sellable_units' => min(9999, $this->wallet->sellableUnits($account, $product)),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function product(SignatureProduct $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'validity' => $product->validity->value,
            'validity_label' => $product->validity->label,
            'container' => $product->container->value,
            'container_label' => $product->container->label,
            'credit_unit_price' => $product->credit_unit_price,
            'suggested_retail_price' => $product->suggested_retail_price,
            'currency' => $product->currency,
            'is_active' => $product->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function requestSummary(SignatureRequest $request): array
    {
        return [
            'id' => $request->id,
            'code' => $request->code(),
            'applicant_name' => $request->applicantName(),
            'document_number' => $request->document_number,
            'company_name' => $request->company_name,
            'product_name' => $request->product_name,
            'source' => $request->source->value,
            'source_label' => $request->source->label,
            'status' => $request->status->value,
            'status_label' => $request->status->label,
            'sale_price' => $request->sale_price,
            'created_at' => $request->created_at,
        ];
    }

    /**
     * @param  Closure(SignatureRequestDocument): string  $documentUrl
     * @return array<string, mixed>
     */
    public function requestDetail(SignatureRequest $request, Closure $documentUrl): array
    {
        return [
            ...$this->requestSummary($request),
            ...$this->requestForm($request),
            'applicant_type_label' => $request->applicant_type->label,
            'document_type_label' => $request->document_type->label,
            'gender_label' => $request->gender->label,
            'validity_label' => $request->validity->label,
            'container_label' => $request->container->label,
            'is_editable' => $request->status->isEditable(),
            'provider_token' => $request->provider_token,
            'last_error' => $request->last_error,
            'certificate_serial' => $request->certificate_serial,
            'certificate_valid_from' => $request->certificate_valid_from,
            'certificate_valid_to' => $request->certificate_valid_to,
            'submitted_at' => $request->submitted_at,
            'issued_at' => $request->issued_at,
            'missing_documents' => collect($request->missingDocuments())
                ->map(fn (SignatureDocumentKind $kind) => ['kind' => $kind->value, 'label' => $kind->label])
                ->values(),
            'documents' => $request->documents
                ->map(fn (SignatureRequestDocument $document) => [
                    'id' => $document->id,
                    'kind' => $document->kind->value,
                    'kind_label' => $document->kind->label,
                    'original_name' => $document->original_name,
                    'size' => $document->size,
                    'url' => $documentUrl($document),
                ])
                ->values(),
            'events' => $request->events
                ->sortByDesc('created_at')
                ->map(fn (SignatureRequestEvent $event) => [
                    'id' => $event->id,
                    'status' => $event->status->value,
                    'status_label' => $event->status->label,
                    'description' => $event->description,
                    'actor_name' => $event->actor_name,
                    'created_at' => $event->created_at,
                ])
                ->values(),
        ];
    }

    /**
     * The editable fields, as the form expects them.
     *
     * @return array<string, mixed>
     */
    public function requestForm(SignatureRequest $request): array
    {
        return [
            'signature_product_id' => $request->signature_product_id,
            'applicant_type' => $request->applicant_type->value,
            'sale_price' => $request->sale_price,
            'first_names' => $request->first_names,
            'first_surname' => $request->first_surname,
            'second_surname' => $request->second_surname,
            'document_type' => $request->document_type->value,
            'document_number' => $request->document_number,
            'fingerprint_code' => $request->fingerprint_code,
            'personal_ruc' => $request->personal_ruc,
            'gender' => $request->gender->value,
            'birth_date' => $request->birth_date->toDateString(),
            'nationality' => $request->nationality,
            'mobile_phone' => $request->mobile_phone,
            'landline_phone' => $request->landline_phone,
            'email' => $request->email,
            'province' => $request->province,
            'city' => $request->city,
            'address' => $request->address,
            'company_name' => $request->company_name,
            'company_ruc' => $request->company_ruc,
            'position' => $request->position,
            'legal_representative_first_names' => $request->legal_representative_first_names,
            'legal_representative_surnames' => $request->legal_representative_surnames,
            'legal_representative_document_type' => $request->legal_representative_document_type?->value,
            'legal_representative_document_number' => $request->legal_representative_document_number,
        ];
    }

    /**
     * Option lists for the application form.
     *
     * @param  Collection<int, SignatureProduct>  $products
     * @param  array<string, string|null>  $prices  tenant retail price per product id
     * @return array<string, mixed>
     */
    public function formOptions(Collection $products, array $prices = []): array
    {
        return [
            'products' => $products->map(fn (SignatureProduct $product) => [
                ...$this->product($product),
                'retail_price' => $prices[$product->id] ?? $product->suggested_retail_price,
            ])->values(),
            'applicantTypes' => SignatureApplicantType::toArray(),
            'documentTypes' => IdentityDocumentType::toArray(),
            'genders' => Gender::toArray(),
            'documentKinds' => collect(SignatureDocumentKind::cases())
                ->map(fn (SignatureDocumentKind $kind) => [
                    'kind' => $kind->value,
                    'label' => $kind->label,
                    'accept' => '.'.str_replace(',', ',.', $kind->mimes()),
                    'accepts_images' => str_contains($kind->mimes(), 'jpg'),
                    'accepts_pdf' => str_contains($kind->mimes(), 'pdf'),
                    // Camera to open on phones: front for the selfie, rear for documents.
                    'capture' => match (true) {
                        $kind->equals(SignatureDocumentKind::Selfie()) => 'user',
                        str_contains($kind->mimes(), 'jpg') => 'environment',
                        default => null,
                    },
                ])
                ->values(),
            'requiredDocuments' => collect(SignatureApplicantType::cases())
                ->mapWithKeys(fn (SignatureApplicantType $type) => [
                    $type->value => collect(SignatureDocumentKind::requiredFor($type))
                        ->map(fn (SignatureDocumentKind $kind) => $kind->value)
                        ->values(),
                ]),
        ];
    }
}
