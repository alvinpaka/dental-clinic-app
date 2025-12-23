<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(ClinicSeeder::class);

        // Create super-admin user (no clinic assignment)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Get first clinic for demo users
        $clinic = \App\Models\Clinic::first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'clinic_id' => $clinic->id,
            ]
        );
        $admin->assignRole('admin');

        $receptionist = User::firstOrCreate(
            ['email' => 'receptionist@example.com'],
            [
                'name' => 'Receptionist User',
                'password' => Hash::make('password'),
                'clinic_id' => $clinic->id,
            ]
        );
        $receptionist->assignRole('receptionist');

        $dentist = User::firstOrCreate(
            ['email' => 'dentist@example.com'],
            [
                'name' => 'Dentist User',
                'password' => Hash::make('password'),
                'clinic_id' => $clinic->id,
            ]
        );
        $dentist->assignRole('dentist');

        $assistant = User::firstOrCreate(
            ['email' => 'assistant@example.com'],
            [
                'name' => 'Assistant User',
                'password' => Hash::make('password'),
                'clinic_id' => $clinic->id,
            ]
        );
        $assistant->assignRole('assistant');

        User::factory(5)->create()->each(function (User $user) use ($clinic) {
            $user->clinic_id = $clinic->id;
            $user->save();
            $user->assignRole('assistant');
        });

        // Seed patients for the first clinic
        $this->call(PatientSeeder::class);

        // Default Clinical Note Templates
        \App\Models\ClinicalNoteTemplate::firstOrCreate(
            ['name' => 'Routine Checkup'],
            [
                'subjective' => 'Patient presents for routine check-up and cleaning. No acute complaints.',
                'objective' => 'Vitals stable. Oral hygiene good. No active caries. Gingiva healthy.',
                'assessment' => 'Routine oral health maintenance.',
                'plan' => 'Prophylaxis completed. OHI reinforced. Recall in 6 months.',
                'active' => true,
            ]
        );
        \App\Models\ClinicalNoteTemplate::firstOrCreate(
            ['name' => 'Caries on #46'],
            [
                'subjective' => 'Intermittent sensitivity on lower right molar for several days.',
                'objective' => 'Caries present on #46 occlusal. No swelling. Percussion negative.',
                'assessment' => 'Dental caries on #46.',
                'plan' => 'Composite restoration planned. Risks/benefits discussed. Follow-up in 2 weeks.',
                'active' => true,
            ]
        );
    }
}
