<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Provisioning()
 * @method static self Active()
 * @method static self Suspended()
 */
final class TenantStatus extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Provisioning' => 'Aprovisionando',
            'Active' => 'Activo',
            'Suspended' => 'Suspendido',
        ];
    }
}
