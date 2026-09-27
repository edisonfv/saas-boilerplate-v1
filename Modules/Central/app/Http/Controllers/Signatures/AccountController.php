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
use App\Models\Tenant;
use App\Services\Signatures\SignaturePresenter;
use App\Services\Signatures\SignatureWallet;
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

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $tenants = Tenant::query()
            ->with(['signatureAccount.balances', 'domains'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('id', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")))
            ->orderByRaw('company_name is null, company_name')
            ->paginate(20)
            ->withQueryString();

        $soldByTenant = SignatureProviderRequest::query()
            ->whereIn('tenant_id', $tenants->getCollection()->modelKeys())
            ->selectRaw('tenant_id, count(*) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $tenants = $tenants
            ->through(fn (Tenant $tenant) => [
                'id' => $tenant->getTenantKey(),
                'company_name' => $tenant->company_name,
                'domain' => $tenant->domains->first()?->domain,
                'signatures_sold' => (int) ($soldByTenant[$tenant->getTenantKey()] ?? 0),
                'account' => $tenant->signatureAccount ? [
                    'affiliation_mode' => $tenant->signatureAccount->affiliation_mode->value,
                    'affiliation_mode_label' => $tenant->signatureAccount->affiliation_mode->label,
                    'is_active' => $tenant->signatureAccount->is_active,
                    'credit_limit' => $tenant->signatureAccount->credit_limit,
                    'credit_used' => $tenant->signatureAccount->credit_used,
                    'available_units' => (int) $tenant->signatureAccount->balances->sum('available_units'),
                ] : null,
            ]);

        return Inertia::render('Central/Signatures/Accounts/Index', [
            'tenants' => $tenants,
            'filters' => ['search' => $search],
            'stats' => [
                'accounts' => SignatureAccount::query()->count(),
                'credit' => SignatureAccount::query()->where('affiliation_mode', SignatureAffiliationMode::Credit()->value)->count(),
                'prepaid' => SignatureAccount::query()->where('affiliation_mode', SignatureAffiliationMode::Prepaid()->value)->count(),
                'sold_this_month' => SignatureProviderRequest::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function show(Request $request, Tenant $tenant): Response
    {
        $account = $tenant->signatureAccount()->first();

        return Inertia::render('Central/Signatures/Accounts/Show', [
            'tenant' => [
                'id' => $tenant->getTenantKey(),
                'company_name' => $tenant->company_name,
            ],
            'account' => $this->presenter->account($account),
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
