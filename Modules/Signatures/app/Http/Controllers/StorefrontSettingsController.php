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
                'is_published' => $storefront->is_published,
                'headline' => $storefront->headline,
                'description' => $storefront->description,
                'contact_email' => $storefront->contact_email,
                'contact_phone' => $storefront->contact_phone,
                'whatsapp' => $storefront->whatsapp,
                'prices' => (object) ($storefront->prices ?? []),
            ],
            'products' => SignatureProduct::query()->active()->orderBy('credit_unit_price')->get()
                ->map(fn (SignatureProduct $product) => $presenter->product($product))
                ->values(),
            'publicUrl' => route('tenant.signatures.storefront.show'),
        ]);
    }

    public function update(UpdateStorefrontRequest $request): RedirectResponse
    {
        SignatureStorefront::current()->update([
            ...$request->safe()->except('prices'),
            'prices' => $request->safe()->collect('prices')
                ->filter(fn (mixed $price) => $price !== null && $price !== '')
                ->map(fn (mixed $price) => number_format((float) $price, 2, '.', ''))
                ->all(),
        ]);

        return back()->with('status', 'signature-storefront-updated');
    }
}
