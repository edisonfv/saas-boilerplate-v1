<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A prepaid bundle: `quantity` signatures of one product for a special
 * total `price`. Selling it to a prepaid tenant credits its balance.
 *
 * @property string $id
 * @property string $signature_product_id
 * @property string $name
 * @property int $quantity
 * @property string $price
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SignatureProduct $product
 */
#[Fillable(['signature_product_id', 'name', 'quantity', 'price', 'is_active'])]
class SignaturePackage extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<SignatureProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SignatureProduct::class, 'signature_product_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
