<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'billing_period' => fake()->randomElement([
                BillingPeriod::Monthly(),
                BillingPeriod::Quarterly(),
                BillingPeriod::Annual(),
            ]),
            'price' => fake()->randomFloat(2, 9, 500),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
