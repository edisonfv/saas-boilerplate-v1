<?php

namespace Database\Factories;

use App\Enums\Action;
use App\Models\Module;
use App\Models\ModulePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModulePermission>
 */
class ModulePermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement([
            Action::Create(),
            Action::View(),
            Action::Update(),
            Action::Delete(),
        ]);

        return [
            'module_id' => Module::factory(),
            'slug' => fake()->unique()->word().'.'.strtolower((string) $action->value),
        ];
    }
}
