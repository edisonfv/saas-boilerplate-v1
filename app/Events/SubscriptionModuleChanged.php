<?php

namespace App\Events;

use App\Enums\ModuleActivationAction;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionModuleChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Module $module,
        public ModuleActivationAction $action,
    ) {}
}
