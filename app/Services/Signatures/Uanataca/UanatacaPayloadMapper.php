<?php

namespace App\Services\Signatures\Uanataca;

use App\Enums\Gender;
use App\Enums\IdentityDocumentType;
use App\Enums\SignatureApplicantType;
use App\Enums\SignatureContainer;
use App\Enums\SignatureDocumentKind;
use App\Enums\SignatureValidity;
use App\Services\Signatures\SignatureApplication;
use Illuminate\Support\Str;

/**
 * Translates a SignatureApplication into the body of Uanataca's
 * `POST /v4/solicitud` (docs: uanataca.ec/api/documentacion/firmas_larga_duracion).
 * All values are strings; files go base64-encoded; empty optional fields
 * are omitted.
 */
class UanatacaPayloadMapper
{
    /**
     * @return array<string, string>
     */
    public function solicitud(SignatureApplication $application): array
    {
        $payload = [
            'tipo_solicitud' => $this->applicantType($application->applicantType),
            'contenedor' => $this->container($application->container),
            'nombres' => Str::upper($application->firstNames),
            'apellido1' => Str::upper($application->firstSurname),
            'apellido2' => $application->secondSurname !== null ? Str::upper($application->secondSurname) : null,
            'tipodocumento' => $this->documentType($application->documentType),
            'numerodocumento' => $application->documentNumber,
            'coddactilar' => $application->fingerprintCode !== null ? Str::upper($application->fingerprintCode) : null,
            'ruc_personal' => $application->personalRuc,
            'sexo' => $application->gender->equals(Gender::Female()) ? 'MUJER' : 'HOMBRE',
            'fecha_nacimiento' => $application->birthDate->format('Y/m/d'),
            'nacionalidad' => Str::upper($application->nationality),
            'telfCelular' => $application->mobilePhone,
            'telfFijo' => $application->landlinePhone,
            'eMail' => $application->email,
            'provincia' => Str::upper($application->province),
            'ciudad' => Str::upper($application->city),
            'direccion' => Str::limit($application->address, 100, ''),
            'vigenciafirma' => $this->validity($application->validity),
        ];

        if ($application->applicantType->requiresCompany()) {
            $payload += [
                // Uanataca rejects quotes and ampersands in the company name.
                'empresa' => Str::upper(str_replace(['"', "'", '&'], ['', '', 'Y'], (string) $application->companyName)),
                'ruc_empresa' => $application->companyRuc,
                'cargo' => Str::upper((string) $application->position),
            ];
        }

        if ($application->applicantType->requiresLegalRepresentative()) {
            $payload += [
                'nombresRL' => Str::upper((string) $application->legalRepresentativeFirstNames),
                'apellidosRL' => Str::upper((string) $application->legalRepresentativeSurnames),
                'tipodocumentoRL' => $application->legalRepresentativeDocumentType !== null
                    ? $this->documentType($application->legalRepresentativeDocumentType)
                    : null,
                'numerodocumentoRL' => $application->legalRepresentativeDocumentNumber,
            ];
        }

        foreach ($application->documents as $kind => $contents) {
            $payload[$this->documentField(SignatureDocumentKind::from($kind))] = base64_encode($contents);
        }

        return array_filter($payload, fn (?string $value) => $value !== null && $value !== '');
    }

    public function applicantType(SignatureApplicantType $type): string
    {
        return match (true) {
            $type->equals(SignatureApplicantType::LegalRepresentative()) => '2',
            $type->equals(SignatureApplicantType::CompanyMember()) => '3',
            default => '1',
        };
    }

    public function container(SignatureContainer $container): string
    {
        return $container->equals(SignatureContainer::Cloud()) ? '2' : '0';
    }

    public function validity(SignatureValidity $validity): string
    {
        return match (true) {
            $validity->equals(SignatureValidity::SevenDays()) => '7 días',
            $validity->equals(SignatureValidity::ThirtyDays()) => '30 días',
            $validity->equals(SignatureValidity::TwoYears()) => '2 años',
            $validity->equals(SignatureValidity::ThreeYears()) => '3 años',
            $validity->equals(SignatureValidity::FourYears()) => '4 años',
            $validity->equals(SignatureValidity::FiveYears()) => '5 años',
            default => '1 año',
        };
    }

    private function documentType(IdentityDocumentType $type): string
    {
        return $type->equals(IdentityDocumentType::Passport()) ? 'PASAPORTE' : 'CEDULA';
    }

    private function documentField(SignatureDocumentKind $kind): string
    {
        return match (true) {
            $kind->equals(SignatureDocumentKind::IdFront()) => 'f_cedulaFront',
            $kind->equals(SignatureDocumentKind::IdBack()) => 'f_cedulaBack',
            $kind->equals(SignatureDocumentKind::Selfie()) => 'f_selfie',
            $kind->equals(SignatureDocumentKind::RucCopy()) => 'f_copiaruc',
            $kind->equals(SignatureDocumentKind::Appointment()) => 'f_nombramiento',
            $kind->equals(SignatureDocumentKind::AppointmentAcceptance()) => 'f_nombramiento2',
            $kind->equals(SignatureDocumentKind::CompanyConstitution()) => 'f_constitucion',
            $kind->equals(SignatureDocumentKind::LegalRepresentativeId()) => 'f_documentoRL',
            $kind->equals(SignatureDocumentKind::LegalRepresentativeAuthorization()) => 'f_autreprelegal',
            default => 'f_adicional2',
        };
    }
}
