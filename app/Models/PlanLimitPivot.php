<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for the plan_limit table (Plan <-> LimitType), carrying the numeric
 * limit value (e.g. users = 5).
 *
 * @property int $value
 */
class PlanLimitPivot extends Pivot {}
