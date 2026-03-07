<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => fake()->boolean(60) ? null : null, // 60% chance of no PO
            'supplier_id' => \App\Models\Supplier::inRandomOrder()->first()?->id ?? \App\Models\Supplier::factory(),
            'delivery_date' => fake()->dateTimeBetween('-30 days', '+30 days'),
            'status' => fake()->randomElement(['pending', 'received', 'partial', 'cancelled']),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
