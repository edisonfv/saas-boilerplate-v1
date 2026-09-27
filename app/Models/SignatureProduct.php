<?php

namespace App\Models;

use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Database\Factories\SignatureProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A signature the platform resells (validity + delivery container).
 * `credit_unit_price` is what a tenant on credit is charged per issued
 * signature; `suggested_retail_price` is only a hint for the tenant's own
 * storefront price.
 *
 * @property string $id
 * @property string $name
 * @property SignatureValidity $validity
 * @property SignatureContainer $container
 * @property string $credit_unit_price
 * @property string|null $suggested_retail_price
 * @property string $currency
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'validity', 'container', 'credit_unit_price', 'suggested_retail_price', 'currency', 'is_active'])]
class SignatureProduct extends Model
{
    /** @use HasFactory<SignatureProductFactory> */
    use CentralConnection, HasFactory, UsesUuidPrimaryKey;

    /**
     * @return HasMany<SignaturePackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(SignaturePackage::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validity' => SignatureValidity::class,
            'container' => SignatureContainer::class,
            'credit_unit_price' => 'decimal:2',
            'suggested_retail_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
