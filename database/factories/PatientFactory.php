<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random clinic or use the first one
        $clinic = Clinic::inRandomOrder()->first() ?? Clinic::first();
        
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'dob' => $this->faker->date('Y-m-d', '2005-12-31'), // Random date between 1950-2005
            'address' => $this->faker->address(),
            'medical_history' => $this->faker->randomElement([
                null,
                'Patient reports no significant medical history. No known allergies.',
                'History of hypertension, controlled with medication. No diabetes.',
                'Type 2 diabetes, well controlled with metformin. No cardiovascular issues.',
                'Asthma, uses inhaler as needed. No other chronic conditions.',
                'Previous knee surgery in 2018. No ongoing complications.',
            ]),
            'allergies' => $this->faker->randomElement([
                null,
                [],
                ['Penicillin'],
                ['Sulfa drugs', 'Latex'],
                ['Nuts', 'Shellfish'],
                ['Aspirin'],
                ['Codeine', 'Local anesthetics'],
            ]),
            'clinic_id' => $clinic->id,
        ];
    }
}
