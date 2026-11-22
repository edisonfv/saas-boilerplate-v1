<?php

namespace App\Console\Commands;

use App\Services\TenantPlanSubscriber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:expire-lapsed')]
#[Description('Mark subscriptions whose billing period or trial ended as expired.')]
class ExpireLapsedSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TenantPlanSubscriber $subscriber): int
    {
        $expired = $subscriber->expireLapsed();

        $this->info($expired === 0 ? 'No lapsed subscriptions.' : "Expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
