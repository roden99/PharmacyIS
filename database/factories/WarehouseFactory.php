<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehousename' => fake()->company() . ' ' . fake()->randomElement(['Warehouse', 'Storage', 'Depot', 'Hub']),
            'status' => fake()->boolean(90), // 90% chance of being active
            'created_by' => null, // Will be set in seeder
            'updated_by' => null, // Will be set in seeder
        ];
    }
}
