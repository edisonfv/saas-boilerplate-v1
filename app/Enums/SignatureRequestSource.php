<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Where a signature request was captured: by a tenant operator in the
 * workspace, or by the end customer on the tenant's public storefront.
 *
 * @method static self Workspace()
 * @method static self Storefront()
 */
final class SignatureRequestSource extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Workspace' => 'Punto de venta',
            'Storefront' => 'Sitio web',
        ];
    }
}
