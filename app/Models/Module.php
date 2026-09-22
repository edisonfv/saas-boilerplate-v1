<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Database\Factories\ModuleFactory;
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
 * @property bool $sellable_as_addon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'is_active', 'sellable_as_addon'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory, UsesUuidPrimaryKey;

    /**
     * @return HasMany<ModulePrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ModulePrice::class);
    }

    /**
     * @return BelongsToMany<Plan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_module');
    }

    /**
     * @return HasMany<Feature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    /**
     * @return HasMany<ModulePermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(ModulePermission::class);
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
            'sellable_as_addon' => 'boolean',
        ];
    }
}
