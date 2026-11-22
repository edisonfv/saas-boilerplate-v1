<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A prepaid, single-use application link (tenant database): staff
 * collects the payment first and sends the customer a signed link; the
 * application made with it arrives already paid, for the invited product.
 * Consumed when that application is created.
 *
 * @property string $id
 * @property string $signature_product_id
 * @property string $product_name
 * @property string $customer_name
 * @property string|null $customer_email
 * @property string|null $customer_phone
 * @property string $amount
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $consumed_at
 * @property string|null $signature_request_id
 * @property string|null $created_by
 * @property string|null $created_by_name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'signature_product_id', 'product_name', 'customer_name', 'customer_email', 'customer_phone', 'amount',
    'expires_at', 'consumed_at', 'signature_request_id', 'created_by', 'created_by_name',
])]
class SignatureInvitation extends Model
{
    use UsesUuidPrimaryKey;

    public function isUsable(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }

    /**
     * @return BelongsTo<SignatureRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class, 'signature_request_id');
    }

    /**
     * @return HasOne<SignaturePayment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(SignaturePayment::class, 'signature_invitation_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
