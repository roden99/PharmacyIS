<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarehouseItem>
 */
class WarehouseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => \App\Models\warehouse::inRandomOrder()->first()?->id ?? \App\Models\warehouse::factory(),
            'product_id' => \App\Models\product::inRandomOrder()->first()?->id ?? \App\Models\product::factory(),
            'quantity' => fake()->numberBetween(0, 1000),
            'created_by' => null, // Will be set in seeder
            'updated_by' => null, // Will be set in seeder
        ];
    }
}
