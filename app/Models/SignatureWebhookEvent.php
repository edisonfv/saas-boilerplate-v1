<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Raw provider webhook as received, kept for auditing and replay. The
 * unique `fingerprint` (hash of the body) makes redelivered webhooks
 * idempotent.
 *
 * @property string $id
 * @property string $provider
 * @property string $fingerprint
 * @property string|null $event_type
 * @property string|null $provider_token
 * @property array<string, mixed> $payload
 * @property CarbonImmutable|null $processed_at
 * @property string|null $error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['provider', 'fingerprint', 'event_type', 'provider_token', 'payload', 'processed_at', 'error'])]
class SignatureWebhookEvent extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
