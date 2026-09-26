<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * A kind of session customers can book (e.g. "Consulta rápida", 30 min).
 * Its duration defines the slot size offered in the booking calendar.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property int $duration_minutes
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description', 'duration_minutes', 'is_active'])]
class SupportAttendanceType extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
