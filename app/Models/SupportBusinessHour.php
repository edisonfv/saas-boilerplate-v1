<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Team-wide opening window for one ISO weekday (1 = Monday ... 7 = Sunday),
 * in config('app.timezone'). A weekday may have several windows.
 *
 * @property string $id
 * @property int $weekday
 * @property string $opens_at
 * @property string $closes_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['weekday', 'opens_at', 'closes_at'])]
class SupportBusinessHour extends Model
{
    use CentralConnection, UsesUuidPrimaryKey;

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
