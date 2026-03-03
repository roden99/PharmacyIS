<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productTypes = [
            'Tablet',
            'Capsule',
            'Syrup',
            'Suspension',
            'Injection',
            'Cream',
            'Ointment',
            'Drops',
            'Inhaler',
            'Solution',
            'Gel',
            'Powder',
            'Patch',
            'Spray'
        ];

        $medicineNames = [
            'Paracetamol',
            'Ibuprofen',
            'Amoxicillin',
            'Azithromycin',
            'Cetirizine',
            'Omeprazole',
            'Metformin',
            'Losartan',
            'Atorvastatin',
            'Aspirin',
            'Ciprofloxacin',
            'Doxycycline',
            'Prednisone',
            'Lisinopril',
            'Metoprolol',
            'Amlodipine',
            'Simvastatin',
            'Levothyroxine',
            'Gabapentin',
            'Hydrochlorothiazide',
            'Clopidogrel',
            'Montelukast',
            'Escitalopram',
            'Rosuvastatin',
            'Pantoprazole'
        ];

        $strengths = ['5mg', '10mg', '20mg', '50mg', '100mg', '250mg', '500mg', '1000mg', '5ml', '10ml'];

        return [
            'isgeneric' => fake()->boolean(40), // 40% chance of being generic
            'productname' => fake()->randomElement($medicineNames) . ' ' .
                fake()->randomElement($strengths) . ' ' .
                fake()->randomElement($productTypes),
            'brand_id' => null, // Will be set in seeder
            'status' => fake()->boolean(95), // 95% chance of being active
            'created_by' => null, // Will be set in seeder
            'updated_by' => null, // Will be set in seeder
        ];
    }
}
