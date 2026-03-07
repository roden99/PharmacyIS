<?php

namespace Database\Seeders;

use App\Models\DeliveryItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryItemSeeder extends Seeder
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

        // Create 200 delivery items (roughly 4 items per delivery)
        DeliveryItem::factory(200)->create()->each(function ($item) use ($userIds) {
            $item->update([
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
