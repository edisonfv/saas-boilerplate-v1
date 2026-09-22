<?php

namespace App\Listeners;

use App\Enums\ModuleActivationAction;
use App\Events\SubscriptionModuleChanged;
use App\Services\TenantModulePermissionSyncer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class SyncTenantModulePermissionsOnActivation implements ShouldQueue
{
    public int $tries = 5;

    public function __construct(private TenantModulePermissionSyncer $syncer) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [1, 5, 10, 30];
    }

    /**
     * Handle the event. Deactivations don't seed/remove anything — permissions
     * and role assignments stay intact in case the tenant re-activates the
     * module later (see docs/architecture/plans-modules-permissions.md section 10).
     */
    public function handle(SubscriptionModuleChanged $event): void
    {
        if ($event->action->equals(ModuleActivationAction::Activated())) {
            $this->syncer->sync($event->tenant, $event->module);
        }
    }

    public function failed(SubscriptionModuleChanged $event, Throwable $exception): void
    {
        report($exception);
    }
}
