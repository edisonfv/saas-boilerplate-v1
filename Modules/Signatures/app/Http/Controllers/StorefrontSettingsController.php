<?php

namespace Modules\Signatures\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SignatureProduct;
use App\Models\SignatureStorefront;
use App\Services\Signatures\SignaturePresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\UpdateStorefrontRequest;

/**
 * The tenant configures its public signature website: copy, contact
 * channels, retail prices and whether it's published.
 */
class StorefrontSettingsController extends Controller
{
    public function edit(SignaturePresenter $presenter): Response
    {
        $storefront = SignatureStorefront::current();

        return Inertia::render('Signatures/Storefront/Edit', [
            'storefront' => [
                'headline' => $storefront->headline,
                'description' => $storefront->description,
                'contact_email' => $storefront->contact_email,
                'contact_phone' => $storefront->contact_phone,
                'whatsapp' => $storefront->whatsapp,
                'whatsapp_message' => $storefront->whatsapp_message ?? SignatureStorefront::DefaultWhatsappMessage,
                'prices' => (object) ($storefront->prices ?? []),
                'uses' => $storefront->resolvedUses(),
                'steps' => $storefront->resolvedSteps(),
                'faqs' => $storefront->resolvedFaqs(),
            ],
            'products' => SignatureProduct::query()->active()->orderBy('credit_unit_price')->get()
                ->map(fn (SignatureProduct $product) => $presenter->product($product))
                ->values(),
            'publicUrl' => url('/'),
        ]);
    }

    public function update(UpdateStorefrontRequest $request): RedirectResponse
    {
        SignatureStorefront::current()->update([
            ...$request->safe()->except(['prices', 'uses', 'steps', 'faqs']),
            'uses' => $this->rows($request->validated('uses'), ['title', 'text']),
            'steps' => $this->rows($request->validated('steps'), ['title', 'text']),
            'faqs' => $this->rows($request->validated('faqs'), ['question', 'answer']),
            'prices' => $request->safe()->collect('prices')
                ->filter(fn (mixed $price) => $price !== null && $price !== '')
                ->map(fn (mixed $price) => number_format((float) $price, 2, '.', ''))
                ->all(),
        ]);

        return back()->with('status', 'signature-storefront-updated');
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
