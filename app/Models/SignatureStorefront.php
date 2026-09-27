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
 * @property list<array{title: string, text: string}>|null $uses
 * @property list<array{title: string, text: string}>|null $steps
 * @property list<array{question: string, answer: string}>|null $faqs
 * @property list<array{bank: string, account_type: string, number: string, holder: string, holder_id: string}>|null $bank_accounts
 * @property list<array{id: string, path: string, alt: string}>|null $hero_slides
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'headline', 'description', 'contact_email', 'contact_phone', 'whatsapp', 'whatsapp_message', 'prices', 'uses',
    'steps', 'faqs', 'bank_accounts', 'hero_slides', 'seo_title', 'seo_description',
])]
class SignatureStorefront extends Model
{
    public const DefaultWhatsappMessage = 'Hola, quiero información para obtener mi firma electrónica.';

    /** Photos the main banner (slider) can hold. */
    public const MaxHeroSlides = 5;

    /**
     * @return list<array{id: string, path: string, alt: string}>
     */
    public function heroSlides(): array
    {
        return $this->hero_slides ?? [];
    }

    /**
     * Default copy of the editable sections, used while the tenant hasn't
     * edited them (NULL column). An empty list hides the section.
     *
     * @var list<array{title: string, text: string}>
     */
    public const DefaultUses = [
        ['title' => 'Facturación electrónica', 'text' => 'Emite facturas, retenciones y notas de crédito autorizadas por el SRI.'],
        ['title' => 'Firma de documentos', 'text' => 'Firma contratos y documentos PDF con la misma validez que tu firma manuscrita.'],
        ['title' => 'Trámites públicos', 'text' => 'Compras públicas, Quipux, IESS, Superintendencias y otros trámites en línea.'],
        ['title' => 'Seguridad jurídica', 'text' => 'Certificado emitido por Uanataca, entidad de certificación acreditada en Ecuador.'],
    ];

    /** @var list<array{title: string, text: string}> */
    public const DefaultSteps = [
        ['title' => 'Llena tu solicitud', 'text' => 'Elige tu firma y completa tus datos en línea, en pocos minutos.'],
        ['title' => 'Sube tus documentos', 'text' => 'Fotos de tu cédula y una selfie desde tu celular. Si es para empresa, sus documentos en PDF.'],
        ['title' => 'Validamos y pagas', 'text' => 'Revisamos tu información y te contactamos para completar el pago.'],
        ['title' => 'Recibe tu firma', 'text' => 'La entidad certificadora valida tu identidad y te envía tu firma por correo.'],
    ];

    /** @var list<array{question: string, answer: string}> */
    public const DefaultFaqs = [
        ['question' => '¿Qué es la firma electrónica?', 'answer' => 'Es un certificado digital que te identifica en internet y tiene la misma validez legal que tu firma manuscrita, según la Ley de Comercio Electrónico del Ecuador.'],
        ['question' => '¿Cuánto tarda la emisión?', 'answer' => 'Depende de la validación de tu identidad por la entidad certificadora. Con documentos claros y completos el proceso es rápido; te avisamos en cada paso.'],
        ['question' => '¿Qué diferencia hay entre archivo y nube?', 'answer' => 'El archivo .p12 se descarga y lo usas desde tu computador o sistema de facturación. En la nube firmas desde cualquier dispositivo sin instalar nada.'],
        ['question' => '¿Qué vigencia debo elegir?', 'answer' => 'Depende de tu uso: para facturar lo habitual es 1 o 2 años. Mientras más larga la vigencia, menor el costo por año.'],
        ['question' => '¿Qué pasa si mi solicitud es rechazada?', 'answer' => 'Te indicamos qué corregir (por ejemplo, una foto poco legible) para que puedas completar tu trámite.'],
    ];

    /**
     * @return list<array{title: string, text: string}>
     */
    public function resolvedUses(): array
    {
        return $this->uses ?? self::DefaultUses;
    }

    /**
     * @return list<array{title: string, text: string}>
     */
    public function resolvedSteps(): array
    {
        return $this->steps ?? self::DefaultSteps;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function resolvedFaqs(): array
    {
        return $this->faqs ?? self::DefaultFaqs;
    }

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
            'uses' => 'array',
            'steps' => 'array',
            'faqs' => 'array',
            'bank_accounts' => 'array',
            'hero_slides' => 'array',
        ];
    }
}
