<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductType>
 */
class ProductTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Brand', 'Generic', 'OTC', 'Prescription', 'Supplement', 'Herbal', 'Veterinary', 'Medical Device', 'Cosmetic', 'Food'];

        $index = fake()->unique()->numberBetween(0, count($types) - 1);

        return [
            'type_name' => $types[$index],
            'status' => fake()->boolean(90),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
