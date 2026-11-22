<?php

namespace Modules\Central\Http\Controllers\Signatures;

use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Http\Controllers\Controller;
use App\Models\SignaturePackage;
use App\Models\SignatureProduct;
use App\Services\Signatures\SignaturePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\Signatures\SaveSignatureProductRequest;
use Modules\Central\Http\Requests\Signatures\StoreSignaturePackageRequest;

/**
 * Central catalog of resold electronic signatures: products (with the
 * unit price charged to tenants on credit) and their prepaid packages.
 */
class ProductController extends Controller
{
    public function __construct(private SignaturePresenter $presenter) {}

    public function index(Request $request): Response
    {
        $products = SignatureProduct::query()
            ->with(['packages' => fn ($query) => $query->orderBy('quantity')])
            ->orderByDesc('is_active')
            ->orderBy('credit_unit_price')
            ->get()
            ->map(fn (SignatureProduct $product) => [
                ...$this->presenter->centralProduct($product),
                'packages' => $product->packages->map(fn (SignaturePackage $package) => $this->package($package))->values(),
            ]);

        return Inertia::render('Central/Signatures/Products/Index', [
            'products' => $products,
            'can' => [
                'create' => $request->user()->can('central.signature-products.create'),
                'update' => $request->user()->can('central.signature-products.update'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Signatures/Products/Create', $this->options());
    }

    public function store(SaveSignatureProductRequest $request): RedirectResponse
    {
        $product = SignatureProduct::create($request->validated());

        return redirect()->route('central.signatures.products.edit', $product)->with('status', 'signature-product-created');
    }

    public function edit(SignatureProduct $product): Response
    {
        $product->load(['packages' => fn ($query) => $query->orderBy('quantity')]);

        return Inertia::render('Central/Signatures/Products/Edit', [
            ...$this->options(),
            'product' => [
                ...$this->presenter->centralProduct($product),
                'packages' => $product->packages->map(fn (SignaturePackage $package) => $this->package($package))->values(),
            ],
        ]);
    }

    public function update(SaveSignatureProductRequest $request, SignatureProduct $product): RedirectResponse
    {
        $product->update($request->validated());

        return back()->with('status', 'signature-product-updated');
    }

    public function toggleActive(SignatureProduct $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->is_active ? 'signature-product-activated' : 'signature-product-deactivated');
    }

    public function storePackage(StoreSignaturePackageRequest $request, SignatureProduct $product): RedirectResponse
    {
        $product->packages()->create($request->validated());

        return back()->with('status', 'signature-package-created');
    }

    public function togglePackage(SignatureProduct $product, SignaturePackage $package): RedirectResponse
    {
        $package->update(['is_active' => ! $package->is_active]);

        return back()->with('status', $package->is_active ? 'signature-package-activated' : 'signature-package-deactivated');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'validities' => SignatureValidity::toArray(),
            'containers' => SignatureContainer::toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function package(SignaturePackage $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'quantity' => $package->quantity,
            'price' => $package->price,
            'unit_price' => number_format((float) $package->price / max(1, $package->quantity), 2, '.', ''),
            'is_active' => $package->is_active,
        ];
    }
}
