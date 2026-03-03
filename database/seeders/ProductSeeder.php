<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have users to reference for created_by and updated_by
        $users = User::all();

        if ($users->isEmpty()) {
            // Create some users if none exist
            $users = User::factory(10)->create();
        }

        $userIds = $users->pluck('id')->toArray();

        // Ensure we have brands to reference
        $brands = Brand::all();

        if ($brands->isEmpty()) {
            // Create some brands if none exist
            $brands = Brand::factory(50)->create()->each(function ($brand) use ($userIds) {
                $brand->update([
                    'created_by' => fake()->randomElement($userIds),
                    'updated_by' => fake()->randomElement($userIds),
                ]);
            });
        }

        $brandIds = $brands->pluck('id')->toArray();

        // Create 2000 products
        Product::factory(2000)->create()->each(function ($product) use ($userIds, $brandIds) {
            $product->update([
                'brand_id' => fake()->randomElement($brandIds),
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
