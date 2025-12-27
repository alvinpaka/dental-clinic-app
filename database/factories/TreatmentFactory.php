<?php

namespace Database\Factories;

use App\Models\Treatment;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\DentalMedicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Treatment>
 */
class TreatmentFactory extends Factory
{
    protected $model = Treatment::class;

    public function definition()
    {
        $clinic = Clinic::inRandomOrder()->first() ?? Clinic::factory()->create();
        $patient = Patient::where('clinic_id', $clinic->id)->inRandomOrder()->first() ?? Patient::factory()->create(['clinic_id' => $clinic->id]);
        $appointment = Appointment::where('clinic_id', $clinic->id)->inRandomOrder()->first() ?? Appointment::factory()->create(['clinic_id' => $clinic->id, 'patient_id' => $patient->id]);

        $procedures = [
            ['name' => 'Dental Cleaning', 'cost' => 60000],
            ['name' => 'Tooth Extraction', 'cost' => 30000],
            ['name' => 'Root Canal', 'cost' => 350000],
            ['name' => 'Dental Filling', 'cost' => 90000],
            ['name' => 'Dental Crown', 'cost' => 450000],
            ['name' => 'Dental Bridge', 'cost' => 500000],
            ['name' => 'Dental Implant', 'cost' => 2500000],
            ['name' => 'Teeth Whitening', 'cost' => 300000],
            ['name' => 'Orthodontic Treatment', 'cost' => 1500000],
            ['name' => 'Periodontal Treatment', 'cost' => 200000],
            ['name' => 'Dental X-Ray', 'cost' => 50000],
            ['name' => 'Consultation', 'cost' => 40000],
        ];

        $procedure = $this->faker->randomElement($procedures);

        return [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'cost' => $procedure['cost'],
            'notes' => $this->faker->optional(0.8)->sentence(10),
            'file_path' => $this->faker->optional(0.3)->filePath(),
            'clinic_id' => $clinic->id,
            // Prescription fields (optional)
            'medicine_id' => $this->faker->optional(0.4)->randomElement([DentalMedicine::inRandomOrder()->first()?->id]),
            'medication' => $this->faker->optional(0.4)->randomElement(['Amoxicillin', 'Ibuprofen', 'Penicillin', 'Clindamycin', 'Azithromycin']),
            'dosage' => $this->faker->optional(0.4)->randomElement(['500mg', '250mg', '100mg', '200mg']),
            'frequency' => $this->faker->optional(0.4)->randomElement(['3 times daily', 'twice daily', 'once daily', 'every 4 hours']),
            'duration' => $this->faker->optional(0.4)->randomElement(['7 days', '10 days', '14 days', '5 days']),
            'prescription_amount' => $this->faker->optional(0.4)->numberBetween(10, 30),
            'prescription_issue_date' => $this->faker->optional(0.4)->date(),
            'prescription_expiry_date' => $this->faker->optional(0.4)->dateBetween('+1 month', '+6 months'),
            'prescription_instructions' => $this->faker->optional(0.4)->sentence(),
            'max_refills' => $this->faker->optional(0.4)->numberBetween(0, 5),
            'prescription_status' => $this->faker->optional(0.4)->randomElement(['active', 'expired', 'discontinued', 'refilled']),
            'refill_count' => $this->faker->optional(0.4)->numberBetween(0, 3),
        ];
    }

    public function cleaning()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => 60000,
            'notes' => 'Routine dental cleaning performed',
        ]);
    }

    public function extraction()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => 30000,
            'notes' => 'Tooth extraction performed',
        ]);
    }

    public function rootCanal()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => 350000,
            'notes' => 'Root canal treatment performed',
        ]);
    }

    public function filling()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => 90000,
            'notes' => 'Dental filling applied',
        ]);
    }

    public function crown()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => 450000,
            'notes' => 'Dental crown placed',
        ]);
    }

    public function implant()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => 2500000,
            'notes' => 'Dental implant procedure',
        ]);
    }

    public function withPrescription()
    {
        return $this->state(fn (array $attributes) => [
            'medicine_id' => DentalMedicine::inRandomOrder()->first()?->id ?? DentalMedicine::factory()->create()->id,
            'medication' => $this->faker->randomElement(['Amoxicillin', 'Ibuprofen', 'Penicillin']),
            'dosage' => $this->faker->randomElement(['500mg', '250mg', '100mg']),
            'frequency' => $this->faker->randomElement(['3 times daily', 'twice daily', 'once daily']),
            'duration' => $this->faker->randomElement(['7 days', '10 days', '14 days']),
            'prescription_amount' => $this->faker->numberBetween(10, 30),
            'prescription_issue_date' => now()->toDateString(),
            'prescription_expiry_date' => now()->addMonths(3)->toDateString(),
            'prescription_instructions' => $this->faker->sentence(),
            'max_refills' => $this->faker->numberBetween(0, 5),
            'prescription_status' => 'active',
            'refill_count' => 0,
        ]);
    }

    public function withoutPrescription()
    {
        return $this->state(fn (array $attributes) => [
            'medicine_id' => null,
            'medication' => null,
            'dosage' => null,
            'frequency' => null,
            'duration' => null,
            'prescription_amount' => null,
            'prescription_issue_date' => null,
            'prescription_expiry_date' => null,
            'prescription_instructions' => null,
            'max_refills' => null,
            'prescription_status' => null,
            'refill_count' => null,
        ]);
    }

    public function forPatient(Patient $patient)
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => $patient->id,
            'clinic_id' => $patient->clinic_id,
        ]);
    }

    public function forClinic(Clinic $clinic)
    {
        return $this->state(fn (array $attributes) => [
            'clinic_id' => $clinic->id,
            'patient_id' => Patient::where('clinic_id', $clinic->id)->inRandomOrder()->first()?->id ?? Patient::factory()->create(['clinic_id' => $clinic->id])->id,
        ]);
    }

    public function withAppointment(Appointment $appointment)
    {
        return $this->state(fn (array $attributes) => [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'clinic_id' => $appointment->clinic_id,
        ]);
    }

    public function expiredPrescription()
    {
        return $this->state(fn (array $attributes) => [
            'prescription_status' => 'expired',
            'prescription_expiry_date' => now()->subDays(1)->toDateString(),
        ]);
    }

    public function activePrescription()
    {
        return $this->state(fn (array $attributes) => [
            'prescription_status' => 'active',
            'prescription_issue_date' => now()->toDateString(),
            'prescription_expiry_date' => now()->addMonths(3)->toDateString(),
        ]);
    }

    public function discontinuedPrescription()
    {
        return $this->state(fn (array $attributes) => [
            'prescription_status' => 'discontinued',
        ]);
    }

    public function withRefills()
    {
        return $this->state(fn (array $attributes) => [
            'max_refills' => $this->faker->numberBetween(1, 5),
            'refill_count' => $this->faker->numberBetween(0, 2),
        ]);
    }

    public function noRefills()
    {
        return $this->state(fn (array $attributes) => [
            'max_refills' => 0,
            'refill_count' => 0,
        ]);
    }

    public function cost(int $cost)
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $cost,
        ]);
    }

    public function lowCost()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $this->faker->numberBetween(30000, 100000),
        ]);
    }

    public function mediumCost()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $this->faker->numberBetween(100000, 500000),
        ]);
    }

    public function highCost()
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $this->faker->numberBetween(500000, 3000000),
        ]);
    }
}
