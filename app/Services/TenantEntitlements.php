<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * What a tenant is currently entitled to, derived from central Subscription +
 * SubscriptionModule rows and the features/limits frozen on the subscription
 * when it was contracted (never the live Plan), and cached centrally. Invalidated by
 * App\Listeners\InvalidateTenantEntitlementsCache whenever
 * App\Events\SubscriptionModuleChanged fires.
 */
class TenantEntitlements
{
    /** @var array<string, bool> */
    private array $accessByTenant = [];

    /**
     * @return Collection<int, string> module slugs currently active for the tenant
     */
    public function activeModules(Tenant $tenant): Collection
    {
        if (! $this->grantsAccess($tenant)) {
            return collect();
        }

        return collect($this->snapshot($tenant)['modules']);
    }

    /**
     * @return Collection<int, string> feature slugs enabled by the tenant's current plan
     */
    public function activeFeatures(Tenant $tenant): Collection
    {
        if (! $this->grantsAccess($tenant)) {
            return collect();
        }

        return collect($this->snapshot($tenant)['features']);
    }

    /**
     * @return array<string, int> limit key => value for the tenant's current plan
     */
    public function effectiveLimits(Tenant $tenant): array
    {
        if (! $this->grantsAccess($tenant)) {
            return [];
        }

        return $this->snapshot($tenant)['limits'];
    }

    /**
     * Modules the tenant contracted, even when its billing period lapsed.
     * Only for surfaces that must keep working while the tenant renews (the
     * public signatures website keeps taking orders, which motivates the
     * renewal). A suspended tenant or a cancelled subscription gets none.
     *
     * @return Collection<int, string>
     */
    public function contractedModules(Tenant $tenant): Collection
    {
        $isContracted = tenancy()->central(function () use ($tenant): bool {
            $subscription = $tenant->subscription()->first();

            return $tenant->operationalStatus()->equals(TenantStatus::Active())
                && $subscription !== null
                && ! $subscription->status->equals(SubscriptionStatus::Cancelled());
        });

        return $isContracted ? collect($this->snapshot($tenant)['modules']) : collect();
    }

    public function forget(Tenant $tenant): void
    {
        unset($this->accessByTenant[(string) $tenant->getTenantKey()]);

        tenancy()->central(fn () => Cache::forget($this->cacheKey($tenant)));
    }

    private function grantsAccess(Tenant $tenant): bool
    {
        $tenantId = (string) $tenant->getTenantKey();

        return $this->accessByTenant[$tenantId] ??= tenancy()->central(
            fn () => $tenant->operationalStatus()->equals(TenantStatus::Active())
                && ($tenant->subscription()->first()?->grantsAccessAt() ?? false),
        );
    }

    /**
     * @return array{modules: list<string>, features: list<string>, limits: array<string, int>}
     */
    private function snapshot(Tenant $tenant): array
    {
        return tenancy()->central(function () use ($tenant): array {
            return Cache::remember($this->cacheKey($tenant), now()->addHour(), function () use ($tenant): array {
                $now = now();
                $subscription = $tenant->subscription()->with([
                    'modules' => fn ($query) => $query
                        ->where('starts_at', '<=', $now)
                        ->where(fn ($endsAt) => $endsAt->whereNull('ends_at')->orWhere('ends_at', '>', $now))
                        ->whereHas('module', fn ($module) => $module->where('is_active', true))
                        ->with('module'),
                    'features' => fn ($query) => $query->where('is_active', true),
                    'limits' => fn ($query) => $query->where('is_active', true),
                ])->first();

                if ($subscription === null) {
                    return ['modules' => [], 'features' => [], 'limits' => []];
                }

                return [
                    'modules' => $subscription->modules->pluck('module.slug')->unique()->values()->all(),
                    'features' => $subscription->features->pluck('slug')->values()->all(),
                    'limits' => $subscription->limits
                        ->mapWithKeys(fn ($limitType) => [$limitType->key => $limitType->pivot->value])
                        ->all(),
                ];
            });
        });
    }

    private function cacheKey(Tenant $tenant): string
    {
        return "tenant_entitlements:{$tenant->getTenantKey()}";
    }
}
