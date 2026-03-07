<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryItem>
 */
class DeliveryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expected = fake()->numberBetween(10, 500);
        $received = fake()->numberBetween(0, $expected);

        return [
            'delivery_id' => \App\Models\Delivery::inRandomOrder()->first()?->id ?? \App\Models\Delivery::factory(),
            'product_id' => \App\Models\product::inRandomOrder()->first()?->id ?? \App\Models\product::factory(),
            'quantity_expected' => $expected,
            'quantity_received' => $received,
            'warehouse_id' => \App\Models\warehouse::inRandomOrder()->first()?->id,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
