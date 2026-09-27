<?php

namespace Modules\Signatures\Http\Controllers;

use App\Enums\SignatureRequestSource;
use App\Http\Controllers\Controller;
use App\Models\SignatureProduct;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\SignatureRequestManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signatures\Http\Requests\StoreStorefrontSignatureRequest;

/**
 * The tenant's public website (on its own subdomain) that promotes its
 * electronic signatures and takes applications from end customers. The
 * applications land as drafts: an operator reviews them, collects the
 * payment and submits them from the workspace (consuming the quota).
 */
class StorefrontController extends Controller
{
    public function __construct(private SignaturePresenter $presenter) {}

    public function show(): Response
    {
        $storefront = $this->publishedStorefront();

        return Inertia::render('Signatures/Storefront/Show', [
            'storefront' => $this->storefrontProps($storefront),
            'products' => $this->products($storefront),
        ]);
    }

    public function create(): Response
    {
        $storefront = $this->publishedStorefront();
        $products = SignatureProduct::query()->active()->orderBy('credit_unit_price')->get();

        return Inertia::render('Signatures/Storefront/Apply', [
            'storefront' => $this->storefrontProps($storefront),
            ...$this->presenter->formOptions(
                $products,
                $products->mapWithKeys(fn (SignatureProduct $product) => [$product->id => $storefront->priceFor($product->id)])->all(),
            ),
        ]);
    }

    public function store(StoreStorefrontSignatureRequest $request, SignatureRequestManager $manager): RedirectResponse
    {
        $storefront = $this->publishedStorefront();
        $product = $request->product();

        $signatureRequest = $manager->create(
            $product,
            [...$request->validated(), 'sale_price' => $storefront->priceFor($product->id) ?? $product->suggested_retail_price],
            $request->documents(),
            SignatureRequestSource::Storefront(),
            actorName: trim($request->string('first_names').' '.$request->string('first_surname')),
        );

        return redirect()->route('tenant.signatures.storefront.show')
            ->with('status', 'signature-application-received')
            ->with('signature_request_code', $signatureRequest->code());
    }

    private function publishedStorefront(): SignatureStorefront
    {
        $storefront = SignatureStorefront::current();

        abort_unless($storefront->is_published, 404);

        return $storefront;
    }

    /**
     * @return array<string, mixed>
     */
    private function storefrontProps(SignatureStorefront $storefront): array
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        return [
            'company_name' => $tenant->company_name ?? $tenant->getTenantKey(),
            'headline' => $storefront->headline,
            'description' => $storefront->description,
            'contact_email' => $storefront->contact_email,
            'contact_phone' => $storefront->contact_phone,
            'whatsapp' => $storefront->whatsapp,
            'received_code' => session('signature_request_code'),
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, validity_label: string, container_label: string, price: string|null}>
     */
    private function products(SignatureStorefront $storefront): array
    {
        return SignatureProduct::query()->active()->orderBy('credit_unit_price')->get()
            ->map(fn (SignatureProduct $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'validity_label' => $product->validity->label,
                'container_label' => $product->container->label,
                'price' => $storefront->priceFor($product->id) ?? $product->suggested_retail_price,
            ])
            ->values()
            ->all();
    }
}
