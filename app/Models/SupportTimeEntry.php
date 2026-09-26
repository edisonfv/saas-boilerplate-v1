<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Time a staff member worked on a ticket. Billable entries on tickets in
 * "Included" mode consume the plan's support hours first.
 *
 * @property string $id
 * @property string $support_ticket_id
 * @property string $central_user_id
 * @property string|null $support_appointment_id
 * @property int $minutes
 * @property string $description
 * @property bool $is_billable
 * @property CarbonImmutable $worked_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['support_ticket_id', 'central_user_id', 'support_appointment_id', 'minutes', 'description', 'is_billable', 'worked_on'])]
class SupportTimeEntry extends Model
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
            'minutes' => 'integer',
            'is_billable' => 'boolean',
            'worked_on' => 'date',
        ];
    }
}
