<?php

namespace App\Models;

use App\Enums\SubscriptionChangeStatus;
use App\Enums\SubscriptionChangeType;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\SubscriptionChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A scheduled or already-applied plan change for a subscription. Upgrades are
 * applied immediately (App\Services\TenantPlanSubscriber::changePlan());
 * downgrades stay Pending until App\Console\Commands\ApplyScheduledSubscriptionChanges
 * applies them at effective_at.
 *
 * @property string $id
 * @property string $subscription_id
 * @property string $from_plan_id
 * @property string $to_plan_id
 * @property SubscriptionChangeType $type
 * @property Carbon $effective_at
 * @property Carbon|null $applied_at
 * @property SubscriptionChangeStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['subscription_id', 'from_plan_id', 'to_plan_id', 'type', 'effective_at', 'applied_at', 'status'])]
class SubscriptionChange extends Model
{
    /** @use HasFactory<SubscriptionChangeFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SubscriptionChangeType::class,
            'status' => SubscriptionChangeStatus::class,
            'effective_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }
}
