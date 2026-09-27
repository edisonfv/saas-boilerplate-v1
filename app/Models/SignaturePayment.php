<?php

namespace App\Models;

use App\Enums\SignaturePaymentMethod;
use App\Enums\SignaturePaymentReview;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment an end customer made to the tenant for a signature (tenant
 * database): a receipt they uploaded (reviewed by staff) or a payment
 * staff registered directly. The receipt is internal to the tenant — it
 * is never sent to the certification authority, so it isn't a
 * SignatureRequestDocument.
 *
 * @property string $id
 * @property string|null $signature_request_id
 * @property string|null $signature_invitation_id
 * @property SignaturePaymentMethod $method
 * @property string $amount
 * @property string|null $reference
 * @property string|null $receipt_disk
 * @property string|null $receipt_path
 * @property string|null $receipt_name
 * @property SignaturePaymentReview $review
 * @property string|null $rejection_reason
 * @property string|null $reported_by_name
 * @property string|null $reviewed_by
 * @property string|null $reviewed_by_name
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'signature_request_id', 'signature_invitation_id', 'method', 'amount', 'reference', 'receipt_disk',
    'receipt_path', 'receipt_name', 'review', 'rejection_reason', 'reported_by_name', 'reviewed_by',
    'reviewed_by_name', 'reviewed_at',
])]
class SignaturePayment extends Model
{
    use UsesUuidPrimaryKey;

    public function hasReceipt(): bool
    {
        return $this->receipt_path !== null;
    }

    /**
     * @return BelongsTo<SignatureRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class, 'signature_request_id');
    }

    /**
     * @return BelongsTo<SignatureInvitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(SignatureInvitation::class, 'signature_invitation_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => SignaturePaymentMethod::class,
            'amount' => 'decimal:2',
            'review' => SignaturePaymentReview::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
