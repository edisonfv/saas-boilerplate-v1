<?php

namespace App\Models;

use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Services\Signatures\Money;
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
 * `provider_cost` is what Uanataca charges central per signature;
 * `credit_unit_price` is what a tenant on credit is charged per issued
 * signature; `suggested_retail_price` is only a hint for the tenant's own
 * storefront price, while `min_retail_price` is the floor it can't sell
 * below (channel price protection).
 *
 * @property string $id
 * @property string $name
 * @property SignatureValidity $validity
 * @property SignatureContainer $container
 * @property string|null $provider_cost
 * @property string $credit_unit_price
 * @property string|null $suggested_retail_price
 * @property string|null $min_retail_price
 * @property string $currency
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name', 'validity', 'container', 'provider_cost', 'credit_unit_price', 'suggested_retail_price', 'min_retail_price',
    'currency', 'is_active',
])]
class SignatureProduct extends Model
{
    /** @use HasFactory<SignatureProductFactory> */
    use CentralConnection, HasFactory, UsesUuidPrimaryKey;

    /**
     * Whether a distributor may sell this product to its customers at the
     * given price (at or above the floor, when central set one).
     */
    public function allowsRetailPrice(string|int|float $price): bool
    {
        return $this->min_retail_price === null
            || Money::toCents($price) >= Money::toCents($this->min_retail_price);
    }

    /**
     * The price a distributor actually charges: its own price (or the
     * suggested one), raised to the floor if central moved it above.
     */
    public function retailPriceFrom(?string $price): ?string
    {
        $price ??= $this->suggested_retail_price;

        if ($price === null || $this->allowsRetailPrice($price)) {
            return $price;
        }

        return $this->min_retail_price;
    }

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
            'provider_cost' => 'decimal:2',
            'credit_unit_price' => 'decimal:2',
            'suggested_retail_price' => 'decimal:2',
            'min_retail_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
