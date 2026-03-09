<?php

namespace Database\Seeders;

use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
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

        // Create 13 product units (matches the predefined units in the factory)
        ProductUnit::factory(13)->create()->each(function ($productUnit) use ($userIds) {
            $productUnit->update([
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
