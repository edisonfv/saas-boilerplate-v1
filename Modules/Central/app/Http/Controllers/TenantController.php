<?php

namespace Modules\Central\Http\Controllers;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantEntitlements;
use App\Services\TenantProvisioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\StoreTenantRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = QueryBuilder::for(Tenant::class)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query
                    ->where(fn (Builder $search) => $search
                        ->where('id', 'like', "%{$value}%")
                        ->orWhere('company_name', 'like', "%{$value}%")
                        ->orWhere('legal_name', 'like', "%{$value}%"))),
                AllowedFilter::callback(
                    'status',
                    fn (Builder $query, string $value) => $query->whereHas(
                        'subscription',
                        fn (Builder $subscription) => $subscription->where('status', $value)
                    )
                ),
            )
            ->allowedSorts('id', 'created_at')
            ->defaultSort('-created_at')
            ->with(['domains', 'subscription.plan'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tenant $tenant) => [
                'id' => $tenant->getTenantKey(),
                'company_name' => $tenant->company_name,
                'domain' => $tenant->domains->first()?->domain,
                'tenant_status' => $tenant->operationalStatusValue(),
                'tenant_status_label' => $tenant->operationalStatusLabel(),
                'plan_name' => $tenant->subscription?->plan?->name,
                'status' => $tenant->subscription?->status?->value,
                'status_label' => $tenant->subscription?->status?->label,
                'trial_ends_at' => $tenant->subscription?->trial_ends_at,
                'created_at' => $tenant->created_at,
            ]);

        return Inertia::render('Central/Tenants/Index', [
            'tenants' => $tenants,
            'statuses' => SubscriptionStatus::toArray(),
            'stats' => [
                'total' => Tenant::count(),
                'withSubscription' => Tenant::has('subscription')->count(),
                'withoutSubscription' => Tenant::doesntHave('subscription')->count(),
            ],
            'can' => [
                'create' => $request->user()->can('central.tenants.create'),
                'impersonate' => $request->user()->can('central.tenants.impersonate'),
                'manage' => $request->user()->can('central.tenants.update'),
            ],
        ]);
    }

    public function show(Request $request, Tenant $tenant, TenantEntitlements $entitlements): Response
    {
        $tenant->load(['domains', 'subscription.plan']);

        $subscription = $tenant->subscription;

        return Inertia::render('Central/Tenants/Show', [
            'can' => [
                'impersonate' => $request->user()->can('central.tenants.impersonate'),
                'manage' => $request->user()->can('central.tenants.update'),
            ],
            'tenant' => [
                'id' => $tenant->getTenantKey(),
                'company_name' => $tenant->company_name,
                'legal_name' => $tenant->legal_name,
                'tax_identifier' => $tenant->tax_identifier,
                'country_code' => $tenant->country_code,
                'timezone' => $tenant->timezone,
                'primary_contact_name' => $tenant->primary_contact_name,
                'primary_contact_email' => $tenant->primary_contact_email,
                'status' => $tenant->operationalStatusValue(),
                'status_label' => $tenant->operationalStatusLabel(),
                'domain' => $tenant->domains->first()?->domain,
                'created_at' => $tenant->created_at,
            ],
            'subscription' => $subscription ? [
                'plan_name' => $subscription->plan->name,
                'billing_period_label' => $subscription->billing_period->label,
                'price' => $subscription->price,
                'currency' => $subscription->currency,
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label,
                'trial_ends_at' => $subscription->trial_ends_at,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
            ] : null,
            'entitlements' => $subscription ? [
                'modules' => $entitlements->activeModules($tenant)->values(),
                'features' => $entitlements->activeFeatures($tenant)->values(),
                'limits' => $entitlements->effectiveLimits($tenant),
            ] : null,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Tenants/Create', [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->whereHas('prices', fn (Builder $query) => $query->where('is_active', true))
                ->with(['prices' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Plan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'billing_periods' => $plan->prices->mapWithKeys(
                        fn ($price) => [$price->billing_period->value => $price->billing_period->label]
                    ),
                ]),
            'billingPeriods' => BillingPeriod::toArray(),
            'centralDomain' => config('tenancy.central_domains.0'),
        ]);
    }

    public function store(StoreTenantRequest $request, TenantProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validated();
        $plan = Plan::findOrFail($request->string('plan_id')->toString());
        $billingPeriod = BillingPeriod::from($request->string('billing_period')->toString());

        try {
            $tenant = $provisioner->provision($validated, $plan, $billingPeriod);
        } catch (Throwable) {
            try {
                Tenant::find($validated['id'])?->delete();
            } catch (Throwable) {
                // Best-effort cleanup — the original error below is what matters to the admin.
            }

            throw ValidationException::withMessages([
                'id' => 'No se pudo aprovisionar el tenant. Verifica que el identificador esté disponible e intenta de nuevo.',
            ]);
        }

        return redirect()->route('central.tenants.show', $tenant)->with('status', 'tenant-created');
    }

    public function toggleStatus(Tenant $tenant, TenantEntitlements $entitlements): RedirectResponse
    {
        $tenant->update([
            'status' => $tenant->operationalStatus()->equals(TenantStatus::Active())
                ? TenantStatus::Suspended()
                : TenantStatus::Active(),
        ]);
        $entitlements->forget($tenant);

        return back()->with('status', $tenant->operationalStatus()->equals(TenantStatus::Active())
            ? 'tenant-activated'
            : 'tenant-suspended');
    }
}
