<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Lifecycle of a signature request. Draft is captured locally and still
 * editable; from Submitted on, the provider owns the state and reports it
 * back through its webhook.
 *
 * @method static self Draft()
 * @method static self Submitted()
 * @method static self InValidation()
 * @method static self UpdateRequested()
 * @method static self Approved()
 * @method static self Issued()
 * @method static self Rejected()
 * @method static self Cancelled()
 * @method static self Suspended()
 * @method static self Revoked()
 */
final class SignatureRequestStatus extends Enum
{
    public function isEditable(): bool
    {
        return $this->equals(self::Draft());
    }

    /**
     * Final states in which the sold unit never became a certificate
     * (candidates for returning it to the tenant's quota).
     */
    public function isUnfulfilled(): bool
    {
        return $this->equals(self::Rejected(), self::Cancelled());
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Draft' => 'Borrador',
            'Submitted' => 'Enviada',
            'InValidation' => 'En validación',
            'UpdateRequested' => 'Corrección solicitada',
            'Approved' => 'Aprobada',
            'Issued' => 'Emitida',
            'Rejected' => 'Rechazada',
            'Cancelled' => 'Cancelada',
            'Suspended' => 'Suspendida',
            'Revoked' => 'Revocada',
        ];
    }
}
