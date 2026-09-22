<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'is_active' => true,
            'sellable_as_addon' => false,
        ];
    }

    /**
     * Mark the module as sellable as a standalone addon.
     */
    public function sellableAsAddon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sellable_as_addon' => true,
        ]);
    }
}
