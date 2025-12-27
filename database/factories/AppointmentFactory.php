<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition()
    {
        $clinic = Clinic::inRandomOrder()->first() ?? Clinic::factory()->create();
        $patient = Patient::where('clinic_id', $clinic->id)->inRandomOrder()->first() ?? Patient::factory()->create(['clinic_id' => $clinic->id]);
        $dentist = User::inRandomOrder()->first() ?? User::factory()->create();

        $startTime = $this->faker->dateTimeBetween('now', '+30 days');
        $endTime = (clone $startTime)->modify('+1 hour');

        return [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show']),
            'type' => $this->faker->randomElement(['checkup', 'cleaning', 'filling', 'extraction', 'root_canal', 'crown', 'emergency', 'consultation']),
            'notes' => $this->faker->optional(0.7)->sentence(),
            'clinic_id' => $clinic->id,
        ];
    }

    public function scheduled()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    public function confirmed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function inProgress()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function cancelled()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function noShow()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
        ]);
    }

    public function today()
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $this->faker->dateTimeBetween('today 8:00am', 'today 6:00pm'),
            'end_time' => function (array $attributes) {
                return (clone $attributes['start_time'])->modify('+1 hour');
            },
        ]);
    }

    public function tomorrow()
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $this->faker->dateTimeBetween('tomorrow 8:00am', 'tomorrow 6:00pm'),
            'end_time' => function (array $attributes) {
                return (clone $attributes['start_time'])->modify('+1 hour');
            },
        ]);
    }

    public function thisWeek()
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $this->faker->dateTimeBetween('now', '+6 days'),
            'end_time' => function (array $attributes) {
                return (clone $attributes['start_time'])->modify('+1 hour');
            },
        ]);
    }

    public function emergency()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'emergency',
            'status' => $this->faker->randomElement(['scheduled', 'in_progress']),
        ]);
    }

    public function checkup()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkup',
        ]);
    }

    public function cleaning()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cleaning',
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

    public function withDentist(User $dentist)
    {
        return $this->state(fn (array $attributes) => [
            'dentist_id' => $dentist->id,
        ]);
    }

    public function duration(int $minutes)
    {
        return $this->state(fn (array $attributes) => [
            'end_time' => function (array $attributes) use ($minutes) {
                return (clone $attributes['start_time'])->modify("+{$minutes} minutes");
            },
        ]);
    }

    public function atTime($startTime)
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => (clone $startTime)->modify('+1 hour'),
        ]);
    }
}
