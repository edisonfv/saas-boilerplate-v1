<?php

namespace Modules\Central\Http\Controllers\Signatures;

use App\Enums\SignatureAffiliationMode;
use App\Enums\SignatureLedgerEntryType;
use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use App\Models\SignatureAccount;
use App\Models\SignatureLedgerEntry;
use App\Models\SignaturePackage;
use App\Models\SignatureProduct;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Services\Signatures\Money;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\SignatureSalesReport;
use App\Services\Signatures\SignatureWallet;
use App\Services\TenantPresenter;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\Signatures\AdjustSignatureUnitsRequest;
use Modules\Central\Http\Requests\Signatures\ConfigureSignatureAccountRequest;
use Modules\Central\Http\Requests\Signatures\RecordSignaturePaymentRequest;
use Modules\Central\Http\Requests\Signatures\SellSignaturePackageRequest;

/**
 * Central management of each tenant's signature quota: affiliation mode
 * (credit/prepaid), credit line, package sales, payments, adjustments and
 * the ledger. All writes go through SignatureWallet.
 */
class AccountController extends Controller
{
    public function __construct(
        private SignatureWallet $wallet,
        private SignaturePresenter $presenter,
    ) {}

    public function show(Request $request, Tenant $tenant, TenantPresenter $tenants, SignatureSalesReport $report): Response
    {
        $account = $tenant->signatureAccount()->first();
        $tenantId = $tenant->getTenantKey();

        return Inertia::render('Central/Tenants/Signatures', [
            'header' => $tenants->header($tenant),
            'account' => $this->presenter->account($account),
            'performance' => $account ? [
                'month' => $report->summary(now()->startOfMonth(), now()->endOfDay(), $tenantId),
                'year' => $report->summary(now()->startOfYear(), now()->endOfDay(), $tenantId),
            ] : null,
            'pricing' => $account ? $this->pricing($tenant, $account) : [],
            'ledger' => $account
                ? $account->ledgerEntries()->with(['product', 'author'])->withExists('reversal')->latest()->paginate(20, pageName: 'movimientos')
                    ->withQueryString()
                    ->through(fn (SignatureLedgerEntry $entry) => [
                        'id' => $entry->id,
                        'type' => $entry->type->value,
                        'type_label' => $entry->type->label,
                        'product_name' => $entry->product?->name,
                        'units' => $entry->units,
                        'amount' => $entry->amount,
                        'reference' => $entry->reference,
                        'description' => $entry->description,
                        'author' => $entry->author?->name,
                        'is_refundable' => $entry->type->equals(SignatureLedgerEntryType::Consumption())
                            && ! $entry->reversal_exists,
                        'created_at' => $entry->created_at,
                    ])
                : null,
            'sales' => $account
                ? $account->providerRequests()->with('product')->latest()->limit(15)->get()
                    ->map(fn (SignatureProviderRequest $sale) => [
                        'id' => $sale->id,
                        'product_name' => $sale->product?->name,
                        'unit_price' => $sale->unit_price,
                        'sale_price' => $sale->sale_price,
                        'status' => $sale->status->value,
                        'status_label' => $sale->status->label,
                        'provider_token' => $sale->provider_token,
                        'submitted_at' => $sale->submitted_at,
                        'last_event_at' => $sale->last_event_at,
                    ])
                : [],
            'packages' => SignaturePackage::query()->where('is_active', true)
                ->whereHas('product', fn (Builder $query) => $query->where('is_active', true))
                ->with('product')->orderBy('quantity')->get()
                ->map(fn (SignaturePackage $package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'product_name' => $package->product->name,
                    'quantity' => $package->quantity,
                    'price' => $package->price,
                ]),
            'products' => SignatureProduct::query()->orderBy('credit_unit_price')->get(['id', 'name']),
            'affiliationModes' => SignatureAffiliationMode::toArray(),
            'can' => [
                'update' => $request->user()->can('central.signature-accounts.update'),
                'transactions' => $request->user()->can('central.signature-accounts.transactions'),
            ],
        ]);
    }

    public function configure(ConfigureSignatureAccountRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->wallet->configure(
            $tenant,
            SignatureAffiliationMode::from($request->string('affiliation_mode')->toString()),
            $request->input('credit_limit') ?? 0,
            $request->boolean('is_active'),
            $request->input('notes'),
        );

        return back()->with('status', 'signature-account-saved');
    }

    public function sellPackage(SellSignaturePackageRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->guard(fn () => $this->wallet->purchasePackage(
            $this->account($tenant),
            SignaturePackage::query()->findOrFail($request->string('signature_package_id')->toString()),
            $request->input('reference'),
            $this->staff($request),
        ), 'signature_package_id');

        return back()->with('status', 'signature-package-sold');
    }

    public function recordPayment(RecordSignaturePaymentRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->guard(fn () => $this->wallet->recordPayment(
            $this->account($tenant),
            $request->input('amount'),
            $request->input('reference'),
            $this->staff($request),
        ), 'amount');

        return back()->with('status', 'signature-payment-recorded');
    }

    public function adjustUnits(AdjustSignatureUnitsRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->guard(fn () => $this->wallet->adjustUnits(
            $this->account($tenant),
            SignatureProduct::query()->findOrFail($request->string('signature_product_id')->toString()),
            $request->integer('units'),
            $request->string('description')->toString(),
            $this->staff($request),
        ), 'units');

        return back()->with('status', 'signature-units-adjusted');
    }

    public function refund(Request $request, Tenant $tenant, SignatureLedgerEntry $entry): RedirectResponse
    {
        abort_unless($entry->signature_account_id === $this->account($tenant)->id, 404);

        $this->guard(
            fn () => $this->wallet->refund($entry, "Reverso manual por {$this->staff($request)->name}"),
            'refund',
        );

        return back()->with('status', 'signature-consumption-refunded');
    }

    /**
     * What the distributor pays and charges for each active product: its
     * price to the distributor, the retail price it publishes on its
     * storefront (read from the tenant's own database) and the floor.
     *
     * @return list<array<string, mixed>>
     */
    private function pricing(Tenant $tenant, SignatureAccount $account): array
    {
        $published = rescue(
            fn () => $tenant->run(fn () => SignatureStorefront::query()->first()?->prices ?? []),
            [],
            report: false,
        );

        return SignatureProduct::query()->active()->orderBy('credit_unit_price')->get()
            ->map(function (SignatureProduct $product) use ($account, $published) {
                $unitPrice = $this->wallet->unitPrice($account, $product);
                $ownPrice = $published[$product->id] ?? null;
                $retail = $product->retailPriceFrom($ownPrice === null || $ownPrice === '' ? null : (string) $ownPrice);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'provider_cost' => $product->provider_cost,
                    'unit_price' => $unitPrice,
                    'central_margin' => $product->provider_cost === null
                        ? null
                        : Money::fromCents(Money::toCents($unitPrice) - Money::toCents($product->provider_cost)),
                    'retail_price' => $retail,
                    'uses_own_price' => $ownPrice !== null && $ownPrice !== '',
                    'suggested_retail_price' => $product->suggested_retail_price,
                    'min_retail_price' => $product->min_retail_price,
                    'distributor_margin' => $retail === null
                        ? null
                        : Money::fromCents(Money::toCents($retail) - Money::toCents($unitPrice)),
                ];
            })
            ->values()
            ->all();
    }

    private function account(Tenant $tenant): SignatureAccount
    {
        return $tenant->signatureAccount()->first()
            ?? throw ValidationException::withMessages(['affiliation_mode' => 'Configura primero la afiliación del tenant.']);
    }

    private function staff(Request $request): CentralUser
    {
        /** @var CentralUser */
        return $request->user();
    }

    private function guard(callable $action, string $field): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }
}
