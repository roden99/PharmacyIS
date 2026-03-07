<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => \App\Models\Supplier::inRandomOrder()->first()?->id ?? \App\Models\Supplier::factory(),
            'podate' => fake()->dateTimeBetween('-60 days', '+60 days'),
            'status' => fake()->randomElement(['pending', 'approved', 'completed', 'cancelled']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
