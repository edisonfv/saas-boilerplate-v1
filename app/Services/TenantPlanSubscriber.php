<?php

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\ModuleActivationAction;
use App\Enums\ModuleSource;
use App\Enums\SubscriptionChangeStatus;
use App\Enums\SubscriptionChangeType;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionModuleChanged;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Models\SubscriptionModule;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Subscribes a tenant to a plan and manages plan changes. First-time signup
 * (subscribe()) creates the Subscription and its SubscriptionModule rows and
 * dispatches SubscriptionModuleChanged for each one, so permissions get
 * seeded into the tenant automatically. changePlan()/applyChange() handle
 * upgrades (applied immediately) and downgrades (scheduled — see
 * docs/architecture/plans-modules-permissions.md section 3 and
 * App\Console\Commands\ApplyScheduledSubscriptionChanges).
 *
 * Subscribing and applying a plan change both freeze the plan's current
 * terms (price for the billing period, features, limits) onto the
 * subscription, so later edits to the Plan never alter what an existing
 * tenant contracted. A plan change replaces those terms entirely with the
 * target plan's current ones; addons are kept, except any the new plan now
 * includes, which are ended and replaced by plan-sourced modules.
 */
class TenantPlanSubscriber
{
    public function __construct(private TenantEntitlements $entitlements) {}

    public function subscribe(Tenant $tenant, Plan $plan, BillingPeriod $billingPeriod): Subscription
    {
        $price = $this->contractedPrice($plan, $billingPeriod);

        return DB::transaction(function () use ($tenant, $plan, $billingPeriod, $price): Subscription {
            $now = CarbonImmutable::now();

            $subscription = Subscription::create([
                'tenant_id' => $tenant->getTenantKey(),
                'plan_id' => $plan->id,
                'billing_period' => $billingPeriod,
                'price' => $price->price,
                'currency' => $price->currency,
                'status' => $plan->trial_days ? SubscriptionStatus::Trialing() : SubscriptionStatus::Active(),
                'trial_ends_at' => $plan->trial_days ? $now->addDays($plan->trial_days) : null,
                'current_period_start' => $now,
                'current_period_end' => $this->periodEnd($now, $billingPeriod),
            ]);

            $this->freezeFeaturesAndLimits($subscription, $plan);

            foreach ($plan->modules as $module) {
                $subscription->modules()->create([
                    'module_id' => $module->id,
                    'source' => ModuleSource::Plan(),
                    'starts_at' => $now,
                ]);

                SubscriptionModuleChanged::dispatch($tenant, $module, ModuleActivationAction::Activated());
            }

            $this->entitlements->forget($tenant);

            return $subscription;
        }, attempts: 3);
    }

    /**
     * Requests a plan change for a tenant that already has a subscription.
     * Upgrades apply immediately; downgrades are recorded as Pending and
     * applied later by ApplyScheduledSubscriptionChanges, at the end of the
     * current billing period — so already-paid-for access isn't cut short.
     */
    public function changePlan(Tenant $tenant, Plan $newPlan, SubscriptionChangeType $type): SubscriptionChange
    {
        $change = DB::transaction(function () use ($tenant, $newPlan, $type): SubscriptionChange {
            $subscription = $tenant->subscription()->lockForUpdate()->sole();

            $this->contractedPrice($newPlan, $subscription->billing_period);

            $hasPendingChange = SubscriptionChange::query()
                ->where('subscription_id', $subscription->id)
                ->where('status', SubscriptionChangeStatus::Pending()->value)
                ->lockForUpdate()
                ->exists();

            if ($hasPendingChange) {
                throw new \RuntimeException('La suscripcion ya tiene un cambio pendiente.');
            }

            return SubscriptionChange::create([
                'subscription_id' => $subscription->id,
                'from_plan_id' => $subscription->plan_id,
                'to_plan_id' => $newPlan->id,
                'type' => $type,
                'effective_at' => $type->equals(SubscriptionChangeType::Upgrade())
                    ? CarbonImmutable::now()
                    : ($subscription->current_period_end ?? CarbonImmutable::now()),
                'status' => SubscriptionChangeStatus::Pending(),
            ]);
        }, attempts: 3);

        if ($type->equals(SubscriptionChangeType::Upgrade())) {
            $this->applyChange($change);
        }

        return $change->refresh();
    }

    /**
     * Applies a plan change: syncs plan-sourced subscription_modules to the
     * target plan, replaces the frozen terms (price, features, limits) with
     * the target plan's current ones, updates the subscription's plan, and
     * marks the change Applied. Addons the target plan now includes are ended
     * and replaced by plan-sourced rows without interrupting access; the
     * remaining addons are left untouched.
     */
    public function applyChange(SubscriptionChange $change): void
    {
        DB::transaction(function () use ($change): void {
            $lockedChange = SubscriptionChange::query()
                ->whereKey($change->getKey())
                ->lockForUpdate()
                ->with(['toPlan.modules', 'subscription'])
                ->firstOrFail();

            if (! $lockedChange->status->equals(SubscriptionChangeStatus::Pending())) {
                return;
            }

            $subscription = Subscription::query()
                ->whereKey($lockedChange->subscription_id)
                ->lockForUpdate()
                ->firstOrFail();

            $tenant = Tenant::query()->findOrFail($subscription->tenant_id);
            $newPlan = $lockedChange->toPlan;
            $price = $this->contractedPrice($newPlan, $subscription->billing_period);
            $now = CarbonImmutable::now();
            $newModules = $newPlan->modules->keyBy('id');
            $newModuleIds = $newModules->keys();

            $activeSubscriptionModules = $subscription->modules()
                ->with('module')
                ->whereNull('ends_at')
                ->get();

            $activePlanModules = $activeSubscriptionModules->filter(
                fn (SubscriptionModule $subscriptionModule) => $subscriptionModule->source->equals(ModuleSource::Plan())
            );
            $activeAddonModules = $activeSubscriptionModules->filter(
                fn (SubscriptionModule $subscriptionModule) => $subscriptionModule->source->equals(ModuleSource::Addon())
            );

            $gainedModuleIds = $newModuleIds->diff($activePlanModules->pluck('module_id'));
            $lostSubscriptionModules = $activePlanModules->whereNotIn('module_id', $newModuleIds->all());

            foreach ($gainedModuleIds as $moduleId) {
                $subscription->modules()->create([
                    'module_id' => $moduleId,
                    'source' => ModuleSource::Plan(),
                    'starts_at' => $now,
                ]);

                $absorbedAddons = $activeAddonModules->where('module_id', $moduleId);

                if ($absorbedAddons->isNotEmpty()) {
                    $absorbedAddons->each(fn (SubscriptionModule $addon) => $addon->update(['ends_at' => $now]));

                    continue;
                }

                SubscriptionModuleChanged::dispatch($tenant, $newModules->get($moduleId), ModuleActivationAction::Activated());
            }

            foreach ($lostSubscriptionModules as $subscriptionModule) {
                $subscriptionModule->update(['ends_at' => $now]);

                if ($activeAddonModules->contains('module_id', $subscriptionModule->module_id)) {
                    continue;
                }

                SubscriptionModuleChanged::dispatch($tenant, $subscriptionModule->module, ModuleActivationAction::Deactivated());
            }

            $subscription->update([
                'plan_id' => $newPlan->id,
                'price' => $price->price,
                'currency' => $price->currency,
            ]);

            $this->freezeFeaturesAndLimits($subscription, $newPlan);

            $lockedChange->update([
                'status' => SubscriptionChangeStatus::Applied(),
                'applied_at' => $now,
            ]);

            $this->entitlements->forget($tenant);
        }, attempts: 3);
    }

    /**
     * The plan's active price for the billing period — what the tenant
     * contracts. A plan without one can't be subscribed or changed to.
     */
    private function contractedPrice(Plan $plan, BillingPeriod $billingPeriod): PlanPrice
    {
        $price = $plan->prices()
            ->where('billing_period', $billingPeriod->value)
            ->where('is_active', true)
            ->first();

        if ($price === null) {
            throw new \RuntimeException("El plan [{$plan->slug}] no tiene un precio activo para el periodo [{$billingPeriod->label}].");
        }

        return $price;
    }

    /**
     * Replaces the subscription's frozen features and limits with the plan's
     * current ones.
     */
    private function freezeFeaturesAndLimits(Subscription $subscription, Plan $plan): void
    {
        $subscription->features()->sync($plan->features()->pluck('features.id'));

        $subscription->limits()->sync(
            $plan->limits()->get()->mapWithKeys(fn ($limitType) => [
                $limitType->id => ['value' => $limitType->pivot->value],
            ])->all()
        );
    }

    private function periodEnd(CarbonImmutable $from, BillingPeriod $billingPeriod): CarbonImmutable
    {
        return match (true) {
            $billingPeriod->equals(BillingPeriod::Quarterly()) => $from->addMonths(3),
            $billingPeriod->equals(BillingPeriod::Annual()) => $from->addYear(),
            default => $from->addMonth(),
        };
    }
}
