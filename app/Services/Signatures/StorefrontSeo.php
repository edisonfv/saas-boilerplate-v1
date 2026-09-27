<?php

namespace App\Services\Signatures;

use App\Models\SignatureProduct;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Search/social metadata of a tenant's public website. Rendered by the
 * root Blade view (resources/views/app.blade.php) from the `seo` view
 * data, so crawlers and link previews (WhatsApp, Facebook) get it without
 * running JavaScript or depending on the SSR server.
 */
class StorefrontSeo
{
    public function __construct(private StorefrontMedia $media) {}

    /**
     * @param  Collection<int, SignatureProduct>  $products
     * @return array<string, mixed>
     */
    public function landing(SignatureStorefront $storefront, Collection $products): array
    {
        $company = $this->companyName();
        $slides = $this->media->slides($storefront);
        $organizationId = url('/').'#organizacion';

        $graph = [[
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => $company,
            'url' => url('/'),
            'email' => $storefront->contact_email,
            'telephone' => $storefront->contact_phone,
            'image' => $slides[0]['url'] ?? null,
        ]];

        foreach ($products as $product) {
            $price = $product->retailPriceFrom($storefront->priceFor($product->id));

            $graph[] = [
                '@type' => 'Product',
                'name' => $product->name,
                'description' => "Firma electrónica con vigencia de {$product->validity->label} ({$product->container->label}), emitida por Uanataca Ecuador.",
                'brand' => ['@type' => 'Brand', 'name' => 'Uanataca'],
                'offers' => $price === null ? null : [
                    '@type' => 'Offer',
                    'price' => $price,
                    'priceCurrency' => $product->currency,
                    'availability' => 'https://schema.org/InStock',
                    'url' => url('/solicitud?firma='.$product->id),
                    'seller' => ['@id' => $organizationId],
                ],
            ];
        }

        $faqs = $storefront->resolvedFaqs();

        if ($faqs !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ], $faqs),
            ];
        }

        return [
            'title' => $this->landingTitle($storefront),
            'description' => $this->landingDescription($storefront),
            'canonical' => url('/'),
            'image' => $slides[0]['url'] ?? null,
            'image_alt' => $slides[0]['alt'] ?? null,
            'site_name' => $company,
            'robots' => 'index, follow',
            'json_ld' => $this->withoutNulls(['@context' => 'https://schema.org', '@graph' => $graph]),
        ];
    }

    /**
     * An indexable secondary page (e.g. the application form).
     *
     * @return array<string, mixed>
     */
    public function page(string $title, string $description, string $path): array
    {
        return [
            'title' => $this->pageTitle($title),
            'description' => $description,
            'canonical' => url($path),
            'site_name' => $this->companyName(),
            'robots' => 'index, follow',
        ];
    }

    /**
     * Pages behind signed links or with a customer's data: never indexed.
     *
     * @return array<string, mixed>
     */
    public function private(string $title): array
    {
        return [
            'title' => $this->pageTitle($title),
            'robots' => 'noindex, nofollow',
        ];
    }

    public function landingTitle(SignatureStorefront $storefront): string
    {
        return $storefront->seo_title ?: $this->pageTitle('Firma electrónica en Ecuador');
    }

    public function landingDescription(SignatureStorefront $storefront): string
    {
        return $storefront->seo_description ?: $this->defaultDescription($storefront);
    }

    /**
     * The meta description used while the tenant hasn't written one.
     */
    public function defaultDescription(SignatureStorefront $storefront): string
    {
        return Str::limit((string) ($storefront->description ?: $storefront->headline), 157);
    }

    /**
     * "Page | Company": the " | " separator also tells the front end
     * (resources/js/app.ts) not to append the platform name.
     */
    public function pageTitle(string $title): string
    {
        return "{$title} | {$this->companyName()}";
    }

    private function companyName(): string
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        return $tenant->company_name ?? (string) $tenant->getTenantKey();
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function withoutNulls(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->withoutNulls($value) : $value;
        }

        return array_is_list($data) ? array_values($clean) : $clean;
    }
}
