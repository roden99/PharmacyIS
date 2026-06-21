<?php

namespace Database\Seeders;

use App\Models\strength;
use App\Models\User;
use Illuminate\Database\Seeder;

class StrengthSeeder extends Seeder
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

        // Create 50 strengths
        strength::factory(50)->create()->each(function ($strength) use ($userIds) {
            $strength->update([
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
