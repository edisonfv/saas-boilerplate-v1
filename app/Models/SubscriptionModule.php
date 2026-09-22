<?php

namespace App\Models;

use App\Enums\ModuleSource;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\SubscriptionModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A module a subscription currently grants, and why (source: plan | addon).
 * Replaces the implicit "enabled modules = current plan's modules" — this is
 * explicit and lets plan modules and addon modules coexist independently.
 *
 * @property string $id
 * @property string $subscription_id
 * @property string $module_id
 * @property ModuleSource $source
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['subscription_id', 'module_id', 'source', 'starts_at', 'ends_at'])]
class SubscriptionModule extends Model
{
    /** @use HasFactory<SubscriptionModuleFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => ModuleSource::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
