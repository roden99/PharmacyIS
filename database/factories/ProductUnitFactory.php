<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = ['Piece', 'Box', 'Carton', 'Dozen', 'Pack', 'Set', 'Unit', 'Bundle', 'Case', 'Kilogram', 'Gram', 'Liter', 'Meter'];
        $codes = ['PC', 'BOX', 'CTN', 'DOZ', 'PCK', 'SET', 'UNIT', 'BDL', 'CS', 'KG', 'G', 'L', 'M'];

        $index = fake()->unique()->numberBetween(0, count($units) - 1);

        return [
            'unit_name' => $units[$index],
            'unit_code' => $codes[$index],
            'status' => fake()->boolean(90), // 90% chance of being active
            'created_by' => null, // Will be set in seeder
            'updated_by' => null, // Will be set in seeder
        ];
    }
}
