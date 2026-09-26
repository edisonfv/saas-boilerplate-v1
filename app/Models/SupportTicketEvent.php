<?php

namespace App\Models;

use App\Enums\TicketAuthorType;
use App\Enums\TicketEventType;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Append-only audit entry for a ticket: who did what, and when.
 *
 * @property string $id
 * @property string $support_ticket_id
 * @property TicketEventType $type
 * @property TicketAuthorType $actor_type
 * @property string|null $central_user_id
 * @property string $actor_name
 * @property array<string, mixed>|null $data
 * @property CarbonImmutable $created_at
 */
#[Fillable(['support_ticket_id', 'type', 'actor_type', 'central_user_id', 'actor_name', 'data', 'created_at'])]
class SupportTicketEvent extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketEventType::class,
            'actor_type' => TicketAuthorType::class,
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
