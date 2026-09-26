<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A booked support session in a time slot, attended by one technician.
 *
 * @property string $id
 * @property string $support_ticket_id
 * @property string $support_attendance_type_id
 * @property string $support_technician_id
 * @property string|null $tenant_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property AppointmentStatus $status
 * @property string|null $meeting_url
 * @property string $booked_by_name
 * @property string $booked_by_email
 * @property string|null $cancellation_reason
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'support_ticket_id', 'support_attendance_type_id', 'support_technician_id', 'tenant_id', 'starts_at',
    'ends_at', 'status', 'meeting_url', 'booked_by_name', 'booked_by_email', 'cancellation_reason', 'cancelled_at',
])]
class SupportAppointment extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * Appointments that still hold a technician's capacity.
     *
     * @param  Builder<SupportAppointment>  $query
     * @return Builder<SupportAppointment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Scheduled()->value);
    }

    /**
     * @param  Builder<SupportAppointment>  $query
     * @return Builder<SupportAppointment>
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $startsAt, CarbonInterface $endsAt): Builder
    {
        return $query->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }

    public function minutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * @return BelongsTo<SupportAttendanceType, $this>
     */
    public function attendanceType(): BelongsTo
    {
        return $this->belongsTo(SupportAttendanceType::class, 'support_attendance_type_id');
    }

    /**
     * @return BelongsTo<SupportTechnician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(SupportTechnician::class, 'support_technician_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }
}
