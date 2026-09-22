<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\LimitTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $key
 * @property string $name
 * @property string|null $unit
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PlanLimitPivot $pivot
 */
#[Fillable(['key', 'name', 'unit', 'is_active'])]
class LimitType extends Model
{
    /** @use HasFactory<LimitTypeFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Plan, $this, PlanLimitPivot, 'pivot'>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_limit')
            ->using(PlanLimitPivot::class)
            ->withPivot('value');
    }
}
