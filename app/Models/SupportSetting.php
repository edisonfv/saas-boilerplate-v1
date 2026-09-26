<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Global support parameters (single row). Always read through current().
 *
 * @property int $id
 * @property string $hourly_rate
 * @property string $currency
 * @property int $booking_min_notice_hours
 * @property int $booking_max_days_ahead
 * @property int $cancellation_notice_hours
 * @property int $auto_close_days
 * @property int $reopen_window_days
 * @property int $rating_link_days
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'hourly_rate', 'currency', 'booking_min_notice_hours', 'booking_max_days_ahead',
    'cancellation_notice_hours', 'auto_close_days', 'reopen_window_days', 'rating_link_days',
])]
class SupportSetting extends Model
{
    use CentralConnection;

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'booking_min_notice_hours' => 'integer',
            'booking_max_days_ahead' => 'integer',
            'cancellation_notice_hours' => 'integer',
            'auto_close_days' => 'integer',
            'reopen_window_days' => 'integer',
            'rating_link_days' => 'integer',
        ];
    }
}
