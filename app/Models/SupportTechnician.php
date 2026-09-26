<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A staff member who attends booked sessions. `capacity` is how many
 * sessions they can attend at the same time.
 *
 * @property string $id
 * @property string $central_user_id
 * @property int $capacity
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['central_user_id', 'capacity', 'is_active'])]
class SupportTechnician extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<CentralUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(CentralUser::class, 'central_user_id');
    }

    /**
     * @return HasMany<SupportTechnicianShift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(SupportTechnicianShift::class);
    }

    /**
     * @return HasMany<SupportAppointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(SupportAppointment::class);
    }

    /**
     * @return HasMany<SupportBlackout, $this>
     */
    public function blackouts(): HasMany
    {
        return $this->hasMany(SupportBlackout::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
