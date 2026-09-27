<?php

use App\Models\SignatureStorefront;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSignatures();
});

afterEach(function () {
    tenancy()->end();
});

/**
 * The JSON-LD block of a rendered page, decoded.
 *
 * @return array<string, mixed>
 */
function jsonLd(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return json_decode($matches[1] ?? 'null', true) ?? [];
}

function bannerPhoto(string $name = 'banner.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, 1920, 1080);
}

test('the public website ships its SEO metadata in the server HTML', function () {
    [$tenant, $domain] = signatureTenant();
    $tenant->run(fn () => SignatureStorefront::current()->update([
        'prices' => [signatureProduct()->id => '29.90'],
        'contact_email' => 'ventas@acme.test',
    ]));

    $response = $this->get("http://{$domain}/")->assertOk();
    $html = $response->getContent();

    $response
        ->assertSee('<html lang="es"', false)
        ->assertSee('<title>Firma electrónica en Ecuador | Acme S.A.</title>', false)
        ->assertSee('<meta name="description" content="Firma documentos', false)
        ->assertSee("<link rel=\"canonical\" href=\"http://{$domain}\">", false)
        ->assertSee('<meta property="og:title" content="Firma electrónica en Ecuador | Acme S.A.">', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertDontSee('no-referrer', false)
        ->assertInertia(fn (Assert $page) => $page->where('pageTitle', 'Firma electrónica en Ecuador | Acme S.A.'));

    $graph = collect(jsonLd($html)['@graph']);

    expect($graph->firstWhere('@type', 'Organization'))->toMatchArray(['name' => 'Acme S.A.', 'email' => 'ventas@acme.test'])
        ->and($graph->where('@type', 'Product')->firstWhere('name', 'Firma 1 año')['offers'])
        ->toMatchArray(['price' => '29.90', 'priceCurrency' => 'USD'])
        ->and($graph->firstWhere('@type', 'FAQPage')['mainEntity'])->toHaveCount(count(SignatureStorefront::DefaultFaqs));
});

test('the tenant sets its own Google title and description', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->put("http://{$domain}/firmas-electronicas/sitio-web", [
            ...storefrontSettings(),
            'seo_title' => 'Firma electrónica en Quito | Acme',
            'seo_description' => 'Obtén tu firma electrónica en Quito, 100% en línea.',
        ])
        ->assertSessionHasNoErrors();

    auth()->guard('web')->logout();

    $this->get("http://{$domain}/")
        ->assertSee('<title>Firma electrónica en Quito | Acme</title>', false)
        ->assertSee('<meta name="description" content="Obtén tu firma electrónica en Quito, 100% en línea.">', false);
});

test('pages behind customer links are never indexed nor leak their URL', function () {
    [$tenant, $domain] = signatureTenant();
    $request = signatureDraft($tenant, paid: false);
    $path = $tenant->run(fn () => URL::temporarySignedRoute('tenant.signatures.storefront.payment.show', now()->addDay(), ['signatureRequest' => $request->id], absolute: false));

    $this->get("http://{$domain}{$path}")
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<meta name="referrer" content="no-referrer">', false)
        ->assertDontSee('og:title', false)
        ->assertDontSee('María', false);
});

test('robots.txt and sitemap.xml are per domain', function () {
    [, $domain] = signatureTenant();
    [, $plainDomain] = supportTenant('starter');

    $this->get("http://{$domain}/robots.txt")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /firmas-electronicas')
        ->assertSee('Disallow: /solicitud/')
        ->assertSee("Sitemap: http://{$domain}/sitemap.xml");

    $this->get("http://{$domain}/sitemap.xml")
        ->assertOk()
        ->assertSee("<loc>http://{$domain}</loc>", false)
        ->assertSee("<loc>http://{$domain}/solicitud</loc>", false);

    $this->get("http://{$plainDomain}/robots.txt")->assertSee('Disallow: /');

    $this->get('http://'.config('tenancy.central_domains.0').'/robots.txt')
        ->assertSee('Disallow: /central');
});

test('the tenant uploads banner photos, which lead the page and its link preview', function () {
    [$tenant, $domain] = signatureTenant();
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/sitio-web/fotos", [
            'image' => bannerPhoto(),
            'alt' => 'Asesora entregando una firma electrónica',
        ])
        ->assertSessionHas('status', 'signature-banner-photo-added');

    $slide = $tenant->run(fn () => SignatureStorefront::current()->heroSlides()[0]);
    Storage::disk('signature-media')->assertExists($slide['path']);

    auth()->guard('web')->logout();

    $this->get("http://{$domain}/")
        ->assertSee('<meta property="og:image" content="', false)
        ->assertSee('<meta property="og:image:alt" content="Asesora entregando una firma electrónica">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->has('slides', 1)
            ->where('slides.0.alt', 'Asesora entregando una firma electrónica'));
});

test('banner photos are limited, described and removable', function () {
    [$tenant, $domain] = signatureTenant();
    $owner = supportTenantUser($tenant);
    $url = "http://{$domain}/firmas-electronicas/sitio-web/fotos";

    $this->actingAs($owner)->post($url, ['image' => bannerPhoto()])->assertSessionHasErrors('alt');
    $this->actingAs($owner)->post($url, ['image' => UploadedFile::fake()->image('small.jpg', 300, 200), 'alt' => 'Foto'])
        ->assertSessionHasErrors('image');

    foreach (range(1, SignatureStorefront::MaxHeroSlides) as $number) {
        $this->actingAs($owner)->post($url, ['image' => bannerPhoto("b{$number}.jpg"), 'alt' => "Foto {$number}"]);
    }

    $this->actingAs($owner)->post($url, ['image' => bannerPhoto(), 'alt' => 'Una más'])
        ->assertSessionHasErrors(['image' => 'El banner admite hasta 5 fotos. Quita una para agregar otra.']);

    $slides = $tenant->run(fn () => SignatureStorefront::current()->heroSlides());

    // Keep the last two, reversed, with a new description; the rest are deleted.
    $this->actingAs($owner)
        ->put("http://{$domain}/firmas-electronicas/sitio-web", [
            ...storefrontSettings(),
            'hero_slides' => [
                ['id' => $slides[4]['id'], 'alt' => 'Nueva portada'],
                ['id' => $slides[3]['id'], 'alt' => $slides[3]['alt']],
            ],
        ])
        ->assertSessionHasNoErrors();

    $kept = $tenant->run(fn () => SignatureStorefront::current()->heroSlides());

    expect(array_column($kept, 'id'))->toBe([$slides[4]['id'], $slides[3]['id']])
        ->and($kept[0]['alt'])->toBe('Nueva portada');

    Storage::disk('signature-media')->assertMissing($slides[0]['path']);
    Storage::disk('signature-media')->assertExists($slides[4]['path']);
});

test('the settings form must always send the photo list', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->put("http://{$domain}/firmas-electronicas/sitio-web", array_diff_key(storefrontSettings(), ['hero_slides' => true]))
        ->assertSessionHasErrors('hero_slides');
});
