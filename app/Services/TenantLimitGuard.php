<?php

namespace App\Services;

use App\Exceptions\TenantLimitExceeded;
use App\Models\Tenant;

class TenantLimitGuard
{
    public function __construct(private TenantEntitlements $entitlements) {}

    public function allows(Tenant $tenant, string $limitKey, int $currentUsage, int $requestedAmount = 1): bool
    {
        $limit = $this->entitlements->effectiveLimits($tenant)[$limitKey] ?? null;

        return $limit !== null && $currentUsage + $requestedAmount <= $limit;
    }

    public function assertCanConsume(Tenant $tenant, string $limitKey, int $currentUsage, int $requestedAmount = 1): void
    {
        $limit = $this->entitlements->effectiveLimits($tenant)[$limitKey] ?? 0;

        if ($currentUsage + $requestedAmount > $limit) {
            throw new TenantLimitExceeded(
                tenantId: (string) $tenant->getTenantKey(),
                limitKey: $limitKey,
                limit: $limit,
                currentUsage: $currentUsage,
                requestedAmount: $requestedAmount,
            );
        }
    }
}
