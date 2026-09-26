<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A tenant's current commercial state (lives centrally). One row per tenant —
 * scheduled/applied plan changes live in SubscriptionChange.
 *
 * The contracted terms (price, features, limits) are frozen here when the
 * tenant subscribes or changes plan, so editing a Plan never alters what an
 * existing tenant contracted. Entitlements read these, never the live plan.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $plan_id
 * @property BillingPeriod $billing_period
 * @property string|null $price
 * @property string|null $currency
 * @property SubscriptionStatus $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property bool $cancel_at_period_end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'plan_id', 'billing_period', 'price', 'currency', 'status', 'trial_ends_at', 'current_period_start', 'current_period_end', 'cancel_at_period_end'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    public function grantsAccessAt(?CarbonInterface $moment = null): bool
    {
        $moment ??= CarbonImmutable::now();

        if ($this->current_period_start === null || $this->current_period_end === null) {
            return false;
        }

        if ($moment->lt($this->current_period_start) || ! $moment->lt($this->current_period_end)) {
            return false;
        }

        if ($this->status->equals(SubscriptionStatus::Trialing())) {
            return $this->trial_ends_at !== null && $moment->lt($this->trial_ends_at);
        }

        return $this->status->equals(SubscriptionStatus::Active());
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return HasMany<SubscriptionModule, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(SubscriptionModule::class);
    }

    /**
     * Features contracted with the plan, frozen at subscribe/plan-change time.
     *
     * @return BelongsToMany<Feature, $this>
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'subscription_feature');
    }

    /**
     * Limits contracted with the plan, frozen at subscribe/plan-change time.
     *
     * @return BelongsToMany<LimitType, $this, SubscriptionLimitPivot, 'pivot'>
     */
    public function limits(): BelongsToMany
    {
        return $this->belongsToMany(LimitType::class, 'subscription_limit')
            ->using(SubscriptionLimitPivot::class)
            ->withPivot('value');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_period' => BillingPeriod::class,
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }
}
