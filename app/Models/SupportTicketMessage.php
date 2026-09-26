<?php

namespace App\Models;

use App\Enums\TicketAuthorType;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\UsesUuidPrimaryKey;
use App\Models\Contracts\Attachable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A message in a ticket's conversation. Internal notes (`is_internal`) are
 * staff-only and must never be exposed to the requester.
 *
 * @property string $id
 * @property string $support_ticket_id
 * @property TicketAuthorType $author_type
 * @property string|null $central_user_id
 * @property string $author_name
 * @property string $body
 * @property bool $is_internal
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['support_ticket_id', 'author_type', 'central_user_id', 'author_name', 'body', 'is_internal'])]
class SupportTicketMessage extends Model implements Attachable
{
    use CentralConnection, HasAttachments, UsesUuidPrimaryKey;

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
            'author_type' => TicketAuthorType::class,
            'is_internal' => 'boolean',
        ];
    }
}
