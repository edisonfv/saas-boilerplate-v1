<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Who the signature is issued to. Decides which applicant fields and
 * supporting documents are required.
 *
 * @method static self NaturalPerson()
 * @method static self LegalRepresentative()
 * @method static self CompanyMember()
 */
final class SignatureApplicantType extends Enum
{
    public function requiresCompany(): bool
    {
        return ! $this->equals(self::NaturalPerson());
    }

    public function requiresLegalRepresentative(): bool
    {
        return $this->equals(self::CompanyMember());
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'NaturalPerson' => 'Persona natural',
            'LegalRepresentative' => 'Representante legal',
            'CompanyMember' => 'Miembro de empresa',
        ];
    }
}
