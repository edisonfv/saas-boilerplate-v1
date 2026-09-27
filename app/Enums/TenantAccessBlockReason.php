<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Why a tenant workspace is locked (see App\Http\Middleware\EnsureTenantIsActive).
 *
 * @method static self Suspended()
 * @method static self SubscriptionLapsed()
 */
final class TenantAccessBlockReason extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Suspended' => 'Cuenta suspendida',
            'SubscriptionLapsed' => 'Suscripción vencida',
        ];
    }
}
