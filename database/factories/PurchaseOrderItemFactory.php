<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(10, 500);
        $unitPrice = fake()->randomFloat(2, 10, 1000);
        $totalPrice = $quantity * $unitPrice;

        return [
            'purchase_order_id' => \App\Models\PurchaseOrder::inRandomOrder()->first()?->id ?? \App\Models\PurchaseOrder::factory(),
            'product_id' => \App\Models\product::inRandomOrder()->first()?->id ?? \App\Models\product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
