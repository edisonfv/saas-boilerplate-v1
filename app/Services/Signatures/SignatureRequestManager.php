<?php

namespace App\Services\Signatures;

use App\Enums\SignatureApplicantType;
use App\Enums\SignatureDocumentKind;
use App\Enums\SignatureRequestSource;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Captures and maintains signature requests inside the tenant's own
 * database (must run in tenant context). Sending them to the provider is
 * SignatureIssuance's job; applying provider status changes arrives here
 * through applyStatusUpdate().
 */
class SignatureRequestManager
{
    /** Applicant fields a form may set (validated by the Form Requests). */
    public const ApplicantFields = [
        'applicant_type', 'sale_price', 'first_names', 'first_surname', 'second_surname', 'document_type',
        'document_number', 'fingerprint_code', 'personal_ruc', 'gender', 'birth_date', 'nationality',
        'mobile_phone', 'landline_phone', 'email', 'province', 'city', 'address', 'company_name', 'company_ruc',
        'position', 'legal_representative_first_names', 'legal_representative_surnames',
        'legal_representative_document_type', 'legal_representative_document_number',
    ];

    /**
     * @param  array<string, mixed>  $attributes  validated applicant fields
     * @param  array<string, UploadedFile>  $documents  SignatureDocumentKind value => file
     */
    public function create(
        SignatureProduct $product,
        array $attributes,
        array $documents,
        SignatureRequestSource $source,
        ?string $actorId = null,
        ?string $actorName = null,
    ): SignatureRequest {
        return DB::transaction(function () use ($product, $attributes, $documents, $source, $actorId, $actorName) {
            $request = SignatureRequest::create([
                ...$this->applicantAttributes($attributes),
                ...$this->productSnapshot($product),
                'number' => (int) SignatureRequest::query()->lockForUpdate()->max('number') + 1,
                'source' => $source,
                'status' => SignatureRequestStatus::Draft(),
                'created_by' => $actorId,
            ]);

            $this->storeDocuments($request, $documents);
            $this->record($request, $source->equals(SignatureRequestSource::Storefront())
                ? 'Solicitud recibida desde el sitio web'
                : 'Solicitud registrada', $actorName);

            return $request;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, UploadedFile>  $documents  only the slots being replaced
     */
    public function update(
        SignatureRequest $request,
        SignatureProduct $product,
        array $attributes,
        array $documents,
        ?string $actorName = null,
    ): SignatureRequest {
        $this->ensureEditable($request);

        return DB::transaction(function () use ($request, $product, $attributes, $documents, $actorName) {
            $request->update([...$this->applicantAttributes($attributes), ...$this->productSnapshot($product)]);

            $this->storeDocuments($request, $documents);
            $this->pruneDocuments($request);
            $this->record($request, 'Datos de la solicitud actualizados', $actorName);

            return $request;
        });
    }

    public function discard(SignatureRequest $request): void
    {
        $this->ensureEditable($request);

        DB::transaction(function () use ($request) {
            foreach ($request->documents as $document) {
                Storage::disk($document->disk)->delete($document->path);
            }

            $request->delete();
        });
    }

    /**
     * Mirror a provider status change on the tenant's request.
     */
    public function applyStatusUpdate(SignatureRequest $request, ProviderStatusUpdate $update): void
    {
        DB::transaction(function () use ($request, $update) {
            // REQUEST_CREATED may arrive after later events; never go back to "Submitted".
            $isRegression = $update->status->equals(SignatureRequestStatus::Submitted())
                && ! $request->status->equals(SignatureRequestStatus::Submitted());

            if (! $isRegression) {
                $request->status = $update->status;
            }

            if ($update->status->equals(SignatureRequestStatus::Issued())) {
                $request->issued_at ??= now();
                $request->certificate_serial = $update->certificateSerial ?? $request->certificate_serial;
                $request->certificate_valid_from = $update->certificateValidFrom ?? $request->certificate_valid_from;
                $request->certificate_valid_to = $update->certificateValidTo ?? $request->certificate_valid_to;
            }

            $request->save();

            $this->record($request, $update->description, 'Entidad certificadora', [
                'event_type' => $update->eventType,
            ], $update->status);
        });
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function record(
        SignatureRequest $request,
        string $description,
        ?string $actorName = null,
        ?array $payload = null,
        ?SignatureRequestStatus $status = null,
    ): void {
        $request->events()->create([
            'status' => $status ?? $request->status,
            'description' => $description,
            'actor_name' => $actorName,
            'payload' => $payload,
        ]);
    }

    public function ensureEditable(SignatureRequest $request): void
    {
        if (! $request->status->isEditable()) {
            throw new DomainException('La solicitud ya fue enviada a la entidad certificadora y no puede modificarse.');
        }
    }

    /**
     * @param  array<string, UploadedFile>  $documents
     */
    private function storeDocuments(SignatureRequest $request, array $documents): void
    {
        $disk = (string) config('signatures.documents_disk', 'local');

        foreach ($documents as $kind => $file) {
            $kind = SignatureDocumentKind::from($kind);
            $existing = $request->documents()->where('kind', $kind->value)->first();

            $path = $file->storeAs(
                "signature-requests/{$request->id}",
                $kind->value.'-'.now()->format('YmdHis').'.'.$file->guessExtension(),
                $disk,
            );

            if ($existing !== null) {
                Storage::disk($existing->disk)->delete($existing->path);
            }

            $request->documents()->updateOrCreate(['kind' => $kind->value], [
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $request->unsetRelation('documents');
    }

    /**
     * Drop company documents left over after switching the applicant to
     * a type that doesn't use them.
     */
    private function pruneDocuments(SignatureRequest $request): void
    {
        $allowed = collect(SignatureDocumentKind::requiredFor($request->applicant_type))
            ->push(SignatureDocumentKind::AppointmentAcceptance(), SignatureDocumentKind::Additional())
            ->map(fn (SignatureDocumentKind $kind) => $kind->value);

        $request->documents()->whereNotIn('kind', $allowed)->get()->each(function ($document) {
            Storage::disk($document->disk)->delete($document->path);
            $document->delete();
        });

        $request->unsetRelation('documents');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function applicantAttributes(array $attributes): array
    {
        $values = array_intersect_key($attributes, array_flip(self::ApplicantFields));
        $type = SignatureApplicantType::from((string) $values['applicant_type']);

        if (! $type->requiresCompany()) {
            $values = array_merge($values, ['company_name' => null, 'company_ruc' => null, 'position' => null]);
        }

        // "Persona natural" (without RUC) doesn't enable invoicing: never send a RUC for it.
        if (! $type->enablesInvoicing()) {
            $values['personal_ruc'] = null;
        }

        if (! $type->requiresLegalRepresentative()) {
            $values = array_merge($values, [
                'legal_representative_first_names' => null,
                'legal_representative_surnames' => null,
                'legal_representative_document_type' => null,
                'legal_representative_document_number' => null,
            ]);
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function productSnapshot(SignatureProduct $product): array
    {
        return [
            'signature_product_id' => $product->id,
            'product_name' => $product->name,
            'validity' => $product->validity,
            'container' => $product->container,
        ];
    }
}
