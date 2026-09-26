<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 'tenant-'.fake()->unique()->bothify('????-####'),
            'plan_id' => Plan::factory(),
            'billing_period' => BillingPeriod::Monthly(),
            'price' => fake()->randomFloat(2, 10, 200),
            'currency' => 'USD',
            'status' => SubscriptionStatus::Active(),
            'trial_ends_at' => null,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancel_at_period_end' => false,
        ];
    }
}
