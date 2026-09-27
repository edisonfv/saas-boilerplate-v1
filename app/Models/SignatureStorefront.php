<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The tenant's public signature storefront (single row in the tenant's
 * database): marketing copy, contact channels and the tenant's own retail
 * price per central product id. Always read through current().
 *
 * @property int $id
 * @property bool $is_published
 * @property string $headline
 * @property string|null $description
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $whatsapp
 * @property array<string, string|float|int|null>|null $prices
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['is_published', 'headline', 'description', 'contact_email', 'contact_phone', 'whatsapp', 'prices'])]
class SignatureStorefront extends Model
{
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'headline' => 'Obtén tu firma electrónica en minutos',
            'description' => 'Firma documentos, factura electrónicamente y realiza trámites en línea con plena validez legal.',
        ]);
    }

    /**
     * The tenant's retail price for a central product, if it set one.
     */
    public function priceFor(string $productId): ?string
    {
        $price = $this->prices[$productId] ?? null;

        return $price === null || $price === '' ? null : number_format((float) $price, 2, '.', '');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'prices' => 'array',
        ];
    }
}
