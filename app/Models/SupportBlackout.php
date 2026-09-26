<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A period when support is unavailable: a holiday for the whole team
 * (no technician) or one technician's absence.
 *
 * @property string $id
 * @property string|null $support_technician_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property string $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['support_technician_id', 'starts_at', 'ends_at', 'reason'])]
class SupportBlackout extends Model
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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
