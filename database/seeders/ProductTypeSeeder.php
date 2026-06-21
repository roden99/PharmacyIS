<?php

namespace Database\Seeders;

use App\Models\ProductType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $users = User::factory(10)->create();
        }

        $userIds = $users->pluck('id')->toArray();

        ProductType::factory(10)->create()->each(function ($productType) use ($userIds) {
            $productType->update([
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
        });
    }
}
