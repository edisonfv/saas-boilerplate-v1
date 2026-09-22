<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\PlanPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $plan_id
 * @property BillingPeriod $billing_period
 * @property string $price
 * @property string $currency
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['plan_id', 'billing_period', 'price', 'currency', 'is_active'])]
class PlanPrice extends Model
{
    /** @use HasFactory<PlanPriceFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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
            'is_active' => 'boolean',
        ];
    }
}
