<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_users_cannot_access_other_clinic_data()
    {
        // Create two clinics
        $clinic1 = Clinic::factory()->create();
        $clinic2 = Clinic::factory()->create();

        // Create users for each clinic
        $user1 = User::factory()->create(['clinic_id' => $clinic1->id]);
        $user2 = User::factory()->create(['clinic_id' => $clinic2->id]);

        // Create patients for each clinic
        $patient1 = Patient::factory()->create(['clinic_id' => $clinic1->id]);
        $patient2 = Patient::factory()->create(['clinic_id' => $clinic2->id]);

        // User1 should only see clinic1 patients
        $this->actingAs($user1);
        $response = $this->get('/patients');
        $response->assertSee($patient1->name);
        $response->assertDontSee($patient2->name);

        // User2 should only see clinic2 patients
        $this->actingAs($user2);
        $response = $this->get('/patients');
        $response->assertSee($patient2->name);
        $response->assertDontSee($patient1->name);
    }

    public function test_super_admin_can_access_all_clinic_data()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $clinic1 = Clinic::factory()->create();
        $clinic2 = Clinic::factory()->create();

        $patient1 = Patient::factory()->create(['clinic_id' => $clinic1->id]);
        $patient2 = Patient::factory()->create(['clinic_id' => $clinic2->id]);

        $this->actingAs($superAdmin);
        $response = $this->get('/patients');
        $response->assertSee($patient1->name);
        $response->assertSee($patient2->name);
    }

    public function test_treatments_are_scoped_to_clinic()
    {
        $clinic1 = Clinic::factory()->create();
        $clinic2 = Clinic::factory()->create();

        $user1 = User::factory()->create(['clinic_id' => $clinic1->id]);
        $user2 = User::factory()->create(['clinic_id' => $clinic2->id]);

        $treatment1 = Treatment::factory()->create(['clinic_id' => $clinic1->id]);
        $treatment2 = Treatment::factory()->create(['clinic_id' => $clinic2->id]);

        $this->actingAs($user1);
        $response = $this->get('/treatments');
        $response->assertSee($treatment1->procedure);
        $response->assertDontSee($treatment2->procedure);

        $this->actingAs($user2);
        $response = $this->get('/treatments');
        $response->assertSee($treatment2->procedure);
        $response->assertDontSee($treatment1->procedure);
    }

    public function test_appointments_are_scoped_to_clinic()
    {
        $clinic1 = Clinic::factory()->create();
        $clinic2 = Clinic::factory()->create();

        $user1 = User::factory()->create(['clinic_id' => $clinic1->id]);
        $user2 = User::factory()->create(['clinic_id' => $clinic2->id]);

        $appointment1 = Appointment::factory()->create(['clinic_id' => $clinic1->id]);
        $appointment2 = Appointment::factory()->create(['clinic_id' => $clinic2->id]);

        $this->actingAs($user1);
        $response = $this->get('/appointments');
        $response->assertSee($appointment1->patient->name);
        $response->assertDontSee($appointment2->patient->name);

        $this->actingAs($user2);
        $response = $this->get('/appointments');
        $response->assertSee($appointment2->patient->name);
        $response->assertDontSee($appointment1->patient->name);
    }

    public function test_clinic_middleware_blocks_unauthorized_access()
    {
        $user = User::factory()->create(['clinic_id' => null]);

        $this->actingAs($user);
        $response = $this->get('/patients');
        $response->assertStatus(403);
    }

    public function test_inactive_clinic_blocks_access()
    {
        $clinic = Clinic::factory()->create(['is_active' => false]);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);
        $response = $this->get('/patients');
        $response->assertStatus(403);
    }

    public function test_expired_subscription_blocks_access()
    {
        $clinic = Clinic::factory()->create([
            'is_active' => true,
            'subscription_status' => 'expired'
        ]);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user);
        $response = $this->get('/patients');
        $response->assertStatus(403);
    }
}
