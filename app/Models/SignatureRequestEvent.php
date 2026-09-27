<?php

namespace App\Models;

use App\Enums\SignatureRequestStatus;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status timeline entry of a signature request (tenant database).
 *
 * @property string $id
 * @property string $signature_request_id
 * @property SignatureRequestStatus $status
 * @property string $description
 * @property string|null $actor_name
 * @property array<string, mixed>|null $payload
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['signature_request_id', 'status', 'description', 'actor_name', 'payload'])]
class SignatureRequestEvent extends Model
{
    use UsesUuidPrimaryKey;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<SignatureRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class, 'signature_request_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SignatureRequestStatus::class,
            'payload' => 'array',
        ];
    }
}
