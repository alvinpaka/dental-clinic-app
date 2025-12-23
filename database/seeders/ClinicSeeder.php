<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Clinic;

class ClinicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clinics = [
            [
                'name' => 'Dental Care Center',
                'email' => 'info@dentalcare.com',
                'phone' => '+1-555-0101',
                'address' => '123 Main Street, City, State 12345',
                'is_active' => true,
                'subscription_status' => 'active',
            ],
            [
                'name' => 'Smile Dental Clinic',
                'email' => 'contact@smiledental.com',
                'phone' => '+1-555-0102',
                'address' => '456 Oak Avenue, City, State 12345',
                'is_active' => true,
                'subscription_status' => 'trial',
            ],
            [
                'name' => 'Family Dental Practice',
                'email' => 'hello@familydental.com',
                'phone' => '+1-555-0103',
                'address' => '789 Elm Street, City, State 12345',
                'is_active' => false,
                'subscription_status' => 'expired',
            ],
        ];

        foreach ($clinics as $clinic) {
            Clinic::firstOrCreate(
                ['email' => $clinic['email']],
                $clinic
            );
        }

        $this->command->info('Clinics seeded successfully.');
    }
}
