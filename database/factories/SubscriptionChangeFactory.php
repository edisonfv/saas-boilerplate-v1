<?php

namespace Database\Factories;

use App\Enums\SubscriptionChangeStatus;
use App\Enums\SubscriptionChangeType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionChange>
 */
class SubscriptionChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'from_plan_id' => Plan::factory(),
            'to_plan_id' => Plan::factory(),
            'type' => SubscriptionChangeType::Downgrade(),
            'effective_at' => now(),
            'applied_at' => null,
            'status' => SubscriptionChangeStatus::Pending(),
        ];
    }
}
