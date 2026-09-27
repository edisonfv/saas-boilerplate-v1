<?php

namespace App\Models;

use App\Enums\SignatureLedgerEntryType;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Append-only movement of a tenant's signature account. Never updated or
 * deleted: a mistake is corrected with a Refund/Adjustment entry.
 *
 * `units` is the effect on prepaid balance; `amount` is the effect on the
 * money owed (positive = the tenant owes more / paid for units).
 *
 * @property string $id
 * @property string $signature_account_id
 * @property string|null $signature_product_id
 * @property string|null $signature_package_id
 * @property SignatureLedgerEntryType $type
 * @property int $units
 * @property string $amount
 * @property string|null $reference
 * @property string|null $description
 * @property string|null $reverses_entry_id
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SignatureAccount $account
 * @property-read SignatureProduct|null $product
 * @property-read CentralUser|null $author
 * @property-read self|null $reversal
 * @property-read bool|null $reversal_exists
 */
#[Fillable([
    'signature_account_id', 'signature_product_id', 'signature_package_id', 'type', 'units', 'amount',
    'reference', 'description', 'reverses_entry_id', 'created_by',
])]
class SignatureLedgerEntry extends Model
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
     * @return BelongsTo<CentralUser, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'created_by');
    }

    /**
     * The entry that reversed this one, if any.
     *
     * @return HasOne<self, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_entry_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SignatureLedgerEntryType::class,
            'units' => 'integer',
            'amount' => 'decimal:2',
        ];
    }
}
