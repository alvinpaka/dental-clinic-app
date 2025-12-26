<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicFactory extends Factory
{
    protected $model = \App\Models\Clinic::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company() . ' Dental Clinic',
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'is_active' => true,
            'subscription_status' => 'active',
            'subscription_plan' => 'basic',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#64748B',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'business_hours' => [
                ['day' => 'monday', 'open' => '09:00', 'close' => '17:00', 'closed' => false],
                ['day' => 'tuesday', 'open' => '09:00', 'close' => '17:00', 'closed' => false],
                ['day' => 'wednesday', 'open' => '09:00', 'close' => '17:00', 'closed' => false],
                ['day' => 'thursday', 'open' => '09:00', 'close' => '17:00', 'closed' => false],
                                ['day' => 'friday', 'open' => '09:00', 'close' => '17:00', 'closed' => false],
                ['day' => 'saturday', 'open' => '09:00', 'close' => '12:00', 'closed' => false],
                ['day' => 'sunday', 'open' => '00:00', 'close' => '00:00', 'closed' => true]
            ],
            'settings' => [
                'appointment_reminder_hours' => 24,
                'payment_reminder_days' => 7,
                'auto_invoice' => false,
                'patient_portal' => false,
            ],
        ];
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function trial()
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function expired()
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'expired',
            'subscription_ends_at' => now()->subDays(1),
        ]);
    }

    public function cancelled()
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'cancelled',
        ]);
    }

    public function pro()
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'pro',
        ]);
    }

    public function enterprise()
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'enterprise',
        ]);
    }
}