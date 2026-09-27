<?php

namespace Modules\Signatures\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SignatureProduct;
use App\Models\SignatureStorefront;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\StorefrontMedia;
use App\Services\Signatures\StorefrontSeo;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\UpdateStorefrontRequest;
use Modules\Signatures\Http\Requests\UploadStorefrontImageRequest;

/**
 * The tenant configures its public signature website: banner photos,
 * copy, search snippet (SEO), contact channels, bank accounts and retail
 * prices.
 */
class StorefrontSettingsController extends Controller
{
    public function edit(SignaturePresenter $presenter, StorefrontMedia $media, StorefrontSeo $seo): Response
    {
        $storefront = SignatureStorefront::current();

        return Inertia::render('Signatures/Storefront/Edit', [
            'storefront' => [
                'headline' => $storefront->headline,
                'description' => $storefront->description,
                'seo_title' => $storefront->seo_title ?? '',
                'seo_description' => $storefront->seo_description ?? '',
                'contact_email' => $storefront->contact_email,
                'contact_phone' => $storefront->contact_phone,
                'whatsapp' => $storefront->whatsapp,
                'whatsapp_message' => $storefront->whatsapp_message ?? SignatureStorefront::DefaultWhatsappMessage,
                'prices' => (object) ($storefront->prices ?? []),
                'uses' => $storefront->resolvedUses(),
                'steps' => $storefront->resolvedSteps(),
                'faqs' => $storefront->resolvedFaqs(),
                'bank_accounts' => $storefront->bank_accounts ?? [],
                'hero_slides' => $media->slides($storefront),
            ],
            // What search engines show while the SEO fields are empty.
            'seoDefaults' => [
                'title' => $seo->pageTitle('Firma electrónica en Ecuador'),
                'description' => $seo->defaultDescription($storefront),
            ],
            'maxSlides' => SignatureStorefront::MaxHeroSlides,
            'products' => SignatureProduct::query()->active()->orderBy('credit_unit_price')->get()
                ->map(fn (SignatureProduct $product) => $presenter->product($product))
                ->values(),
            'publicUrl' => url('/'),
        ]);
    }

    public function update(UpdateStorefrontRequest $request, StorefrontMedia $media): RedirectResponse
    {
        $storefront = SignatureStorefront::current();

        $storefront->update([
            ...$request->safe()->except(['prices', 'uses', 'steps', 'faqs', 'bank_accounts', 'hero_slides']),
            'uses' => $this->rows($request->validated('uses'), ['title', 'text']),
            'steps' => $this->rows($request->validated('steps'), ['title', 'text']),
            'faqs' => $this->rows($request->validated('faqs'), ['question', 'answer']),
            'bank_accounts' => $this->rows($request->validated('bank_accounts'), ['bank', 'account_type', 'number', 'holder', 'holder_id']),
            'prices' => $request->safe()->collect('prices')
                ->filter(fn (mixed $price) => $price !== null && $price !== '')
                ->map(fn (mixed $price) => number_format((float) $price, 2, '.', ''))
                ->all(),
        ]);

        // Banner photos: order and alt texts; the ones removed are deleted.
        $media->sync($storefront, $this->rows($request->validated('hero_slides'), ['id', 'alt']));

        return back()->with('status', 'signature-storefront-updated');
    }

    public function storeImage(UploadStorefrontImageRequest $request, StorefrontMedia $media): RedirectResponse
    {
        try {
            $media->add(SignatureStorefront::current(), $request->file('image'), $request->string('alt')->trim()->toString());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['image' => $exception->getMessage()]);
        }

        return back()->with('status', 'signature-banner-photo-added');
    }

    /**
     * A validated repeatable section as a clean list with only its keys.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    private function rows(array $rows, array $keys): array
    {
        return array_values(array_map(
            fn (array $row) => array_map('trim', array_intersect_key($row, array_flip($keys))),
            $rows,
        ));
    }
}
