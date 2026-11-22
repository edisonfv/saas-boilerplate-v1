<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Prepaid units a tenant holds of one product (running total of the
 * ledger, maintained by App\Services\Signatures\SignatureWallet).
 *
 * @property string $id
 * @property string $signature_account_id
 * @property string $signature_product_id
 * @property int $available_units
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SignatureProduct $product
 */
#[Fillable(['signature_account_id', 'signature_product_id', 'available_units'])]
class SignatureBalance extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<SignatureAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(SignatureAccount::class, 'signature_account_id');
    }

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
            'available_units' => 'integer',
        ];
    }
}
