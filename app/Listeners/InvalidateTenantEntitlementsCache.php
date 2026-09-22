<?php

namespace App\Listeners;

use App\Events\SubscriptionModuleChanged;
use App\Services\TenantEntitlements;

class InvalidateTenantEntitlementsCache
{
    public function __construct(private TenantEntitlements $entitlements) {}

    /**
     * Runs inline (not queued) — cheap, and the gate middlewares should never
     * see a stale value after a module is activated or deactivated.
     */
    public function handle(SubscriptionModuleChanged $event): void
    {
        $this->entitlements->forget($event->tenant);
    }
}
