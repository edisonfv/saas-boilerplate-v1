<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Models\Module;
use App\Models\ModulePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModulePrice>
 */
class ModulePriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'billing_period' => fake()->randomElement([
                BillingPeriod::Monthly(),
                BillingPeriod::Quarterly(),
                BillingPeriod::Annual(),
            ]),
            'price' => fake()->randomFloat(2, 5, 200),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
