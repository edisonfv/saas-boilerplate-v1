<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The tenant's public signature website (single row in the tenant's
 * database), served at the root of its subdomain: marketing copy, contact
 * channels (WhatsApp with a pre-filled message) and the tenant's own
 * retail price per central product id. Always read through current().
 *
 * @property int $id
 * @property string $headline
 * @property string|null $description
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $whatsapp
 * @property string|null $whatsapp_message
 * @property array<string, string|float|int|null>|null $prices
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['headline', 'description', 'contact_email', 'contact_phone', 'whatsapp', 'whatsapp_message', 'prices'])]
class SignatureStorefront extends Model
{
    public const DefaultWhatsappMessage = 'Hola, quiero información para obtener mi firma electrónica.';

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'headline' => 'Obtén tu firma electrónica en minutos',
            'description' => 'Firma documentos, factura electrónicamente y realiza trámites en línea con plena validez legal, 100% en línea.',
            'whatsapp_message' => self::DefaultWhatsappMessage,
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
     * wa.me link with the configured (or given) pre-filled message, or null
     * when the tenant hasn't set a WhatsApp number.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        $number = preg_replace('/\D/', '', (string) $this->whatsapp);

        if ($number === '' || $number === null) {
            return null;
        }

        return "https://wa.me/{$number}?text=".rawurlencode($message ?? $this->whatsapp_message ?? self::DefaultWhatsappMessage);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prices' => 'array',
        ];
    }
}
