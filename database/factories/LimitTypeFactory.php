<?php

namespace Database\Factories;

use App\Models\LimitType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LimitType>
 */
class LimitTypeFactory extends Factory
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
            'key' => Str::slug($name, '_'),
            'name' => Str::title($name),
            'unit' => null,
        ];
    }
}
