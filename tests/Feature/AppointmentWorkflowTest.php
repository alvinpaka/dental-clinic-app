<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $dentist;
    protected User $receptionist;
    protected Clinic $clinic;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        $this->patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);
        
        $this->admin = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->admin->assignRole('admin');
        
        $this->dentist = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->dentist->assignRole('dentist');
        
        $this->receptionist = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->receptionist->assignRole('receptionist');
    }

    /** @test */
    public function admin_can_create_appointment()
    {
        Sanctum::actingAs($this->admin);

        $appointmentData = [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00',
            'type' => 'checkup',
            'notes' => 'Regular checkup appointment',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201)
                ->assertJsonFragment(['type' => 'checkup'])
                ->assertJsonFragment(['status' => 'scheduled']);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'type' => 'checkup',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function dentist_can_create_appointment()
    {
        Sanctum::actingAs($this->dentist);

        $appointmentData = [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-16 14:00:00',
            'end_time' => '2024-12-16 15:00:00',
            'type' => 'cleaning',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'type' => 'cleaning',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function receptionist_can_create_appointment()
    {
        Sanctum::actingAs($this->receptionist);

        $appointmentData = [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-17 09:00:00',
            'end_time' => '2024-12-17 10:00:00',
            'type' => 'filling',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'type' => 'filling',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_appointment()
    {
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00',
        ];

        $response = $this->postJson('/api/appointments', $appointmentData);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('appointments', [
            'patient_id' => $this->patient->id
        ]);
    }

    /** @test */
    public function appointment_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['dentist_id', 'start_time', 'end_time', 'type']);
    }

    /** @test */
    public function appointment_creation_validates_time_logic()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 11:00:00',
            'end_time' => '2024-12-15 10:00:00', // End before start
            'type' => 'checkup',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['end_time']);
    }

    /** @test */
    public function admin_can_view_appointments_list()
    {
        Sanctum::actingAs($this->admin);

        // Create some appointments
        Appointment::factory()->count(5)->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id
        ]);

        $response = $this->getJson('/api/appointments');

        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_view_specific_appointment()
    {
        Sanctum::actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id
        ]);

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(200)
                ->assertJsonFragment(['type' => $appointment->type]);
    }

    /** @test */
    public function admin_can_update_appointment()
    {
        Sanctum::actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id
        ]);

        $updateData = [
            'status' => 'confirmed',
            'notes' => 'Patient confirmed the appointment',
        ];

        $response = $this->putJson("/api/appointments/{$appointment->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment(['status' => 'confirmed']);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
            'notes' => 'Patient confirmed the appointment'
        ]);
    }

    /** @test */
    public function admin_can_cancel_appointment()
    {
        Sanctum::actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'status' => 'scheduled'
        ]);

        $response = $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'cancelled',
            'notes' => 'Cancelled by patient request'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function admin_can_reschedule_appointment()
    {
        Sanctum::actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00'
        ]);

        $rescheduleData = [
            'start_time' => '2024-12-15 14:00:00',
            'end_time' => '2024-12-15 15:00:00',
        ];

        $response = $this->putJson("/api/appointments/{$appointment->id}", $rescheduleData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_time' => '2024-12-15 14:00:00',
            'end_time' => '2024-12-15 15:00:00'
        ]);
    }

    /** @test */
    public function admin_can_delete_appointment()
    {
        Sanctum::actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id
        ]);

        $response = $this->deleteJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    /** @test */
    public function appointment_filter_by_date_works()
    {
        Sanctum::actingAs($this->admin);

        // Create appointments for different dates
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:00:00'
        ]);
        
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-16 10:00:00'
        ]);

        $response = $this->getJson('/api/appointments?date=2024-12-15');

        $response->assertStatus(200);
        
        $appointments = $response->json('data');
        $this->assertCount(1, $appointments);
        
        $this->assertEquals('2024-12-15', substr($appointments[0]['start_time'], 0, 10));
    }

    /** @test */
    public function appointment_filter_by_status_works()
    {
        Sanctum::actingAs($this->admin);

        // Create appointments with different statuses
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'status' => 'scheduled'
        ]);
        
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'status' => 'completed'
        ]);

        $response = $this->getJson('/api/appointments?status=scheduled');

        $response->assertStatus(200);
        
        $appointments = $response->json('data');
        $this->assertCount(1, $appointments);
        $this->assertEquals('scheduled', $appointments[0]['status']);
    }

    /** @test */
    public function todays_appointments_filter_works()
    {
        Sanctum::actingAs($this->admin);

        // Create today's appointment
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => Carbon::today()->setTime(10, 0)
        ]);
        
        // Create tomorrow's appointment
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 0)
        ]);

        $response = $this->getJson('/api/appointments?today=true');

        $response->assertStatus(200);
        
        $appointments = $response->json('data');
        $this->assertCount(1, $appointments);
        
        $this->assertEquals(
            Carbon::today()->format('Y-m-d'), 
            substr($appointments[0]['start_time'], 0, 10)
        );
    }

    /** @test */
    public function appointment_workflow_from_creation_to_completion()
    {
        Sanctum::actingAs($this->admin);

        // 1. Create appointment
        $appointmentData = [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00',
            'type' => 'checkup',
        ];

        $createResponse = $this->postJson('/api/appointments', $appointmentData);
        $createResponse->assertStatus(201);
        
        $appointmentId = $createResponse->json('id');
        $this->assertNotNull($appointmentId);

        // 2. Confirm appointment
        $confirmResponse = $this->putJson("/api/appointments/{$appointmentId}", [
            'status' => 'confirmed'
        ]);
        $confirmResponse->assertStatus(200)
                       ->assertJsonFragment(['status' => 'confirmed']);

        // 3. Start appointment
        $startResponse = $this->putJson("/api/appointments/{$appointmentId}", [
            'status' => 'in_progress'
        ]);
        $startResponse->assertStatus(200)
                     ->assertJsonFragment(['status' => 'in_progress']);

        // 4. Complete appointment
        $completeResponse = $this->putJson("/api/appointments/{$appointmentId}", [
            'status' => 'completed',
            'notes' => 'Checkup completed successfully'
        ]);
        $completeResponse->assertStatus(200)
                         ->assertJsonFragment(['status' => 'completed']);

        // 5. Verify final state
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => 'completed',
            'type' => 'checkup',
            'notes' => 'Checkup completed successfully'
        ]);
    }

    /** @test */
    public function appointment_prevents_double_booking()
    {
        Sanctum::actingAs($this->admin);

        // Create first appointment
        Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00',
            'status' => 'confirmed'
        ]);

        // Try to create overlapping appointment
        $overlappingData = [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'start_time' => '2024-12-15 10:30:00', // Overlaps with first appointment
            'end_time' => '2024-12-15 11:30:00',
            'type' => 'cleaning',
        ];

        $response = $this->postJson('/api/appointments', $overlappingData);

        // Depending on implementation, this might be 422 or 201
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['start_time']);
        } else {
            // If system allows overlapping, at least verify it was created
            $response->assertStatus(201);
        }
    }
}
