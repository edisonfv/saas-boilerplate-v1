<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionChangeStatus;
use App\Models\SubscriptionChange;
use App\Services\TenantPlanSubscriber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:apply-scheduled-changes')]
#[Description('Apply pending subscription plan changes (downgrades) whose effective date has passed.')]
class ApplyScheduledSubscriptionChanges extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TenantPlanSubscriber $subscriber): int
    {
        $appliedCount = 0;

        SubscriptionChange::query()
            ->where('status', SubscriptionChangeStatus::Pending()->value)
            ->where('effective_at', '<=', now())
            ->lazyById()
            ->each(function (SubscriptionChange $change) use ($subscriber, &$appliedCount): void {
                $subscriber->applyChange($change);

                $appliedCount++;
                $this->info("Applied subscription change #{$change->id} (subscription #{$change->subscription_id}).");
            });

        if ($appliedCount === 0) {
            $this->info('No due subscription changes to apply.');
        }

        return self::SUCCESS;
    }
}
