<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Spatie\Enum\Laravel\Enum;

/**
 * Preset date ranges of the central sales reports. Custom takes explicit
 * from/to dates.
 *
 * @method static self ThisMonth()
 * @method static self LastMonth()
 * @method static self Last90Days()
 * @method static self ThisYear()
 * @method static self Custom()
 */
final class SalesPeriod extends Enum
{
    /**
     * Start and end (inclusive, whole days) of the preset relative to now.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $now = CarbonImmutable::now();

        return match (true) {
            $this->equals(self::LastMonth()) => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            $this->equals(self::Last90Days()) => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            $this->equals(self::ThisYear()) => [$now->startOfYear(), $now->endOfDay()],
            $this->equals(self::Custom()) => [($from ?? $now->startOfMonth())->startOfDay(), ($to ?? $now)->endOfDay()],
            default => [$now->startOfMonth(), $now->endOfDay()],
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'ThisMonth' => 'Este mes',
            'LastMonth' => 'Mes anterior',
            'Last90Days' => 'Últimos 90 días',
            'ThisYear' => 'Este año',
            'Custom' => 'Personalizado',
        ];
    }
}
