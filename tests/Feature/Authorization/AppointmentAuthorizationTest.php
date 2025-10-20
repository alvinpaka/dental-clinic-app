<?php

namespace Tests\Feature\Authorization;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_receptionist_can_create_appointment(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('receptionist');

        $patient = Patient::create([
            'name' => 'Jane Patient',
            'email' => 'jane.patient@example.com',
            'phone' => '555000111',
            'dob' => now()->subYears(30)->toDateString(),
        ]);

        $response = $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => null,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'type' => 'Dental Cleaning',
            'status' => 'scheduled',
            'notes' => 'Initial cleaning appointment.',
        ]);

        $response->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'type' => 'Dental Cleaning',
            'status' => 'scheduled',
        ]);
    }

    public function test_dentist_cannot_create_appointment(): void
    {
        $dentist = User::factory()->create();
        $dentist->assignRole('dentist');

        $patient = Patient::create([
            'name' => 'John Patient',
            'email' => 'john.patient@example.com',
            'phone' => '555000222',
            'dob' => now()->subYears(32)->toDateString(),
        ]);

        $response = $this->actingAs($dentist)->post(route('appointments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => null,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'type' => 'Root Canal',
            'status' => 'scheduled',
            'notes' => 'Follow-up visit.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('appointments', 0);
    }
}
