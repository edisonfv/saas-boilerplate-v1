<?php

namespace App\Services\Signatures;

use App\Enums\Gender;
use App\Enums\IdentityDocumentType;
use App\Enums\SignatureApplicantType;
use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Models\SignatureRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

/**
 * Everything a provider needs to issue a signature, decoupled from the
 * tenant's Eloquent models. Built inside the tenant context (documents are
 * read from the tenant's own disk).
 */
final readonly class SignatureApplication
{
    /**
     * @param  array<string, string>  $documents  SignatureDocumentKind value => raw file contents
     */
    public function __construct(
        public string $reference,
        public SignatureApplicantType $applicantType,
        public SignatureValidity $validity,
        public SignatureContainer $container,
        public string $firstNames,
        public string $firstSurname,
        public ?string $secondSurname,
        public IdentityDocumentType $documentType,
        public string $documentNumber,
        public ?string $fingerprintCode,
        public ?string $personalRuc,
        public Gender $gender,
        public CarbonImmutable $birthDate,
        public string $nationality,
        public string $mobilePhone,
        public ?string $landlinePhone,
        public string $email,
        public string $province,
        public string $city,
        public string $address,
        public ?string $companyName,
        public ?string $companyRuc,
        public ?string $position,
        public ?string $legalRepresentativeFirstNames,
        public ?string $legalRepresentativeSurnames,
        public ?IdentityDocumentType $legalRepresentativeDocumentType,
        public ?string $legalRepresentativeDocumentNumber,
        public array $documents,
    ) {}

    public static function fromRequest(SignatureRequest $request): self
    {
        $documents = [];

        foreach ($request->documents as $document) {
            $documents[(string) $document->kind->value] = (string) Storage::disk($document->disk)->get($document->path);
        }

        return new self(
            reference: $request->id,
            applicantType: $request->applicant_type,
            validity: $request->validity,
            container: $request->container,
            firstNames: $request->first_names,
            firstSurname: $request->first_surname,
            secondSurname: $request->second_surname,
            documentType: $request->document_type,
            documentNumber: $request->document_number,
            fingerprintCode: $request->fingerprint_code,
            personalRuc: $request->personal_ruc,
            gender: $request->gender,
            birthDate: CarbonImmutable::parse($request->birth_date),
            nationality: $request->nationality,
            mobilePhone: $request->mobile_phone,
            landlinePhone: $request->landline_phone,
            email: $request->email,
            province: $request->province,
            city: $request->city,
            address: $request->address,
            companyName: $request->company_name,
            companyRuc: $request->company_ruc,
            position: $request->position,
            legalRepresentativeFirstNames: $request->legal_representative_first_names,
            legalRepresentativeSurnames: $request->legal_representative_surnames,
            legalRepresentativeDocumentType: $request->legal_representative_document_type,
            legalRepresentativeDocumentNumber: $request->legal_representative_document_number,
            documents: $documents,
        );
    }
}
