<?php

namespace Database\Factories;

use App\Enums\SignatureContainer;
use App\Enums\SignatureValidity;
use App\Models\SignatureProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignatureProduct>
 */
class SignatureProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Firma 1 año',
            'validity' => SignatureValidity::OneYear(),
            'container' => SignatureContainer::File(),
            'credit_unit_price' => 15,
            'suggested_retail_price' => 25,
            'currency' => 'USD',
            'is_active' => true,
        ];
    }

    public function validFor(SignatureValidity $validity): static
    {
        return $this->state(fn () => ['validity' => $validity, 'name' => "Firma {$validity->label}"]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
