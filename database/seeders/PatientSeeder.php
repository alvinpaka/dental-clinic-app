<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a diverse set of patients with realistic data
        $patients = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@email.com',
                'phone' => '+1-555-0101',
                'dob' => '1985-03-15',
                'address' => '123 Main Street, City, State 12345',
                'medical_history' => 'History of hypertension, controlled with medication. No known allergies to medications.',
                'allergies' => ['Penicillin'],
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@email.com',
                'phone' => '+1-555-0102',
                'dob' => '1992-07-22',
                'address' => '456 Oak Avenue, City, State 67890',
                'medical_history' => 'No significant medical history. Previous orthodontic treatment completed in 2010.',
                'allergies' => [],
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael.chen@email.com',
                'phone' => '+1-555-0103',
                'dob' => '1978-11-08',
                'address' => '789 Pine Road, City, State 11223',
                'medical_history' => 'Type 2 diabetes, well controlled with metformin. Takes prophylactic antibiotics for dental procedures.',
                'allergies' => ['Sulfa drugs', 'Latex'],
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@email.com',
                'phone' => '+1-555-0104',
                'dob' => '2001-02-14',
                'address' => '321 Elm Street, City, State 44556',
                'medical_history' => 'Asthma, uses inhaler as needed. No other chronic conditions.',
                'allergies' => ['Nuts', 'Shellfish'],
            ],
            [
                'name' => 'Robert Wilson',
                'email' => 'robert.wilson@email.com',
                'phone' => '+1-555-0105',
                'dob' => '1965-09-30',
                'address' => '654 Maple Drive, City, State 77889',
                'medical_history' => 'Previous knee surgery in 2018. Takes blood thinners (warfarin).',
                'allergies' => ['Aspirin'],
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@email.com',
                'phone' => '+1-555-0106',
                'dob' => '1988-05-18',
                'address' => '987 Cedar Lane, City, State 99001',
                'medical_history' => 'Patient reports anxiety about dental procedures. Requires nitrous oxide for treatment.',
                'allergies' => ['Codeine', 'Local anesthetics'],
            ],
            [
                'name' => 'James Taylor',
                'email' => 'james.taylor@email.com',
                'phone' => '+1-555-0107',
                'dob' => '1973-12-25',
                'address' => '147 Birch Court, City, State 22334',
                'medical_history' => 'History of GERD, takes proton pump inhibitors. No other medical issues.',
                'allergies' => [],
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria.garcia@email.com',
                'phone' => '+1-555-0108',
                'dob' => '1995-08-10',
                'address' => '258 Spruce Way, City, State 55667',
                'medical_history' => 'Pregnant (second trimester). No significant medical history.',
                'allergies' => ['Penicillin'],
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@email.com',
                'phone' => '+1-555-0109',
                'dob' => '1980-04-03',
                'address' => '369 Willow Street, City, State 88990',
                'medical_history' => 'Smoker (1 pack/day). Occasional social drinker. No chronic conditions.',
                'allergies' => [],
            ],
            [
                'name' => 'Jennifer Martinez',
                'email' => 'jennifer.martinez@email.com',
                'phone' => '+1-555-0110',
                'dob' => '1990-06-27',
                'address' => '741 Poplar Avenue, City, State 33445',
                'medical_history' => 'History of migraines. Takes preventive medication. No other health concerns.',
                'allergies' => ['Sulfa drugs'],
            ],
        ];
        foreach ($patients as $patientData) {
            Patient::firstOrCreate(
                ['email' => $patientData['email']],
                $patientData
            );
        }
        // Create additional random patients using factory
        Patient::factory(20)->create();
        $this->command->info('Patients seeded successfully!');
    }
}
