<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\strength>
 */
class StrengthFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'strengthname' => fake()->word() . ' ' . fake()->randomElement(['Strength', 'Capability', 'Skill', 'Asset']),
            'status' => fake()->boolean(90), // 90% chance of being active
            'created_by' => null, // Will be set in seeder
            'updated_by' => null, // Will be set in seeder
        ];
    }
}
