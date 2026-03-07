<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
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

        // Create 30 purchase orders
        PurchaseOrder::factory(30)->create()->each(function ($item) use ($userIds) {
            $item->update([
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
