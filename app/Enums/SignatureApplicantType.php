<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Who the signature is issued to. Decides which applicant fields and
 * supporting documents are required.
 *
 * NaturalPersonWithRuc is a natural person that includes its personal RUC
 * (and a copy of it), which is what enables electronic invoicing; for the
 * provider it's the same natural-person request with those extra fields.
 *
 * @method static self NaturalPerson()
 * @method static self NaturalPersonWithRuc()
 * @method static self LegalRepresentative()
 * @method static self CompanyMember()
 */
final class SignatureApplicantType extends Enum
{
    public function isNaturalPerson(): bool
    {
        return $this->equals(self::NaturalPerson(), self::NaturalPersonWithRuc());
    }

    public function requiresCompany(): bool
    {
        return ! $this->isNaturalPerson();
    }

    public function requiresPersonalRuc(): bool
    {
        return $this->equals(self::NaturalPersonWithRuc());
    }

    public function requiresLegalRepresentative(): bool
    {
        return $this->equals(self::CompanyMember());
    }

    /**
     * Whether the signature can be used for electronic invoicing (SRI).
     */
    public function enablesInvoicing(): bool
    {
        return ! $this->equals(self::NaturalPerson());
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'NaturalPerson' => 'Persona natural',
            'NaturalPersonWithRuc' => 'Persona natural con RUC',
            'LegalRepresentative' => 'Persona jurídica (representante legal)',
            'CompanyMember' => 'Miembro de empresa',
        ];
    }
}
