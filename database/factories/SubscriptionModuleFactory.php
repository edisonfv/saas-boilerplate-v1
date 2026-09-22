<?php

namespace Database\Factories;

use App\Enums\ModuleSource;
use App\Models\Module;
use App\Models\Subscription;
use App\Models\SubscriptionModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionModule>
 */
class SubscriptionModuleFactory extends Factory
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
            'module_id' => Module::factory(),
            'source' => ModuleSource::Plan(),
            'starts_at' => now(),
            'ends_at' => null,
        ];
    }
}
