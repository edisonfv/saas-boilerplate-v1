<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Customer satisfaction (CSAT) for a resolved ticket, attributed to the
 * staff member who resolved it.
 *
 * @property string $id
 * @property string $support_ticket_id
 * @property string|null $central_user_id
 * @property int $stars
 * @property bool $was_resolved
 * @property string|null $comment
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['support_ticket_id', 'central_user_id', 'stars', 'was_resolved', 'comment'])]
class SupportRating extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<CentralUser, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'central_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'was_resolved' => 'boolean',
        ];
    }
}
