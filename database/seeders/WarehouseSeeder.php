<?php

namespace Database\Seeders;

use App\Models\warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
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

        // Create 3 warehouses
        warehouse::factory(3)->create()->each(function ($warehouse) use ($userIds) {
            $warehouse->update([
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
