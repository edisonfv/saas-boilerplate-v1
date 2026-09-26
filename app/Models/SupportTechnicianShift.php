<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A technician's working window for one ISO weekday, in config('app.timezone').
 *
 * @property string $id
 * @property string $support_technician_id
 * @property int $weekday
 * @property string $starts_at
 * @property string $ends_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['support_technician_id', 'weekday', 'starts_at', 'ends_at'])]
class SupportTechnicianShift extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return BelongsTo<SupportTechnician, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(SupportTechnician::class, 'support_technician_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }
}
