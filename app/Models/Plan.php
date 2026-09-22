<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property bool $is_active
 * @property int|null $trial_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'is_active', 'trial_days'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return HasMany<PlanPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    /**
     * @return BelongsToMany<Module, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'plan_module');
    }

    /**
     * @return BelongsToMany<Feature, $this>
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_feature');
    }

    /**
     * @return BelongsToMany<LimitType, $this, PlanLimitPivot, 'pivot'>
     */
    public function limits(): BelongsToMany
    {
        return $this->belongsToMany(LimitType::class, 'plan_limit')
            ->using(PlanLimitPivot::class)
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
            'is_active' => 'boolean',
        ];
    }
}
