<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for the subscription_limit table (Subscription <-> LimitType),
 * carrying the limit value frozen from the plan when it was contracted.
 *
 * @property int $value
 */
class SubscriptionLimitPivot extends Pivot {}
