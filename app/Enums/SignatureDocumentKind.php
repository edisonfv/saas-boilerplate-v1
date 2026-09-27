<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Supporting document slot of a signature request. Which slots are
 * required depends on the applicant type (see requiredFor()).
 *
 * @method static self IdFront()
 * @method static self IdBack()
 * @method static self Selfie()
 * @method static self RucCopy()
 * @method static self Appointment()
 * @method static self AppointmentAcceptance()
 * @method static self CompanyConstitution()
 * @method static self LegalRepresentativeId()
 * @method static self LegalRepresentativeAuthorization()
 * @method static self Additional()
 */
final class SignatureDocumentKind extends Enum
{
    /**
     * @return list<self>
     */
    public static function requiredFor(SignatureApplicantType $applicantType): array
    {
        $identity = [self::IdFront(), self::IdBack(), self::Selfie()];
        $company = [self::RucCopy(), self::Appointment(), self::CompanyConstitution()];

        return match (true) {
            $applicantType->equals(SignatureApplicantType::LegalRepresentative()) => [...$identity, ...$company],
            $applicantType->equals(SignatureApplicantType::CompanyMember()) => [
                ...$identity,
                ...$company,
                self::LegalRepresentativeId(),
                self::LegalRepresentativeAuthorization(),
            ],
            default => $identity,
        };
    }

    /**
     * Allowed extensions for this slot (validation "mimes:" list).
     */
    public function mimes(): string
    {
        return match (true) {
            $this->equals(self::IdFront(), self::IdBack(), self::Selfie()) => 'jpg,jpeg,png',
            $this->equals(self::Additional()) => 'pdf,jpg,jpeg,png',
            default => 'pdf',
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'IdFront' => 'Cédula (anverso)',
            'IdBack' => 'Cédula (reverso)',
            'Selfie' => 'Selfie sosteniendo la cédula',
            'RucCopy' => 'Copia del RUC',
            'Appointment' => 'Nombramiento del representante legal',
            'AppointmentAcceptance' => 'Aceptación del nombramiento',
            'CompanyConstitution' => 'Escritura de constitución',
            'LegalRepresentativeId' => 'Cédula del representante legal',
            'LegalRepresentativeAuthorization' => 'Autorización del representante legal',
            'Additional' => 'Documento adicional',
        ];
    }
}
