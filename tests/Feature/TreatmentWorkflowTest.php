<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\Clinic;
use App\Models\DentalMedicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TreatmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $dentist;
    protected Clinic $clinic;
    protected Patient $patient;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        $this->patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->appointment = Appointment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id
        ]);
        
        $this->admin = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->admin->assignRole('admin');
        
        $this->dentist = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->dentist->assignRole('dentist');
    }

    /** @test */
    public function admin_can_create_treatment()
    {
        Sanctum::actingAs($this->admin);

        $treatmentData = [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 150000,
            'notes' => 'Dental filling performed',
        ];

        $response = $this->postJson('/api/treatments', $treatmentData);

        $response->assertStatus(201)
                ->assertJsonFragment(['cost' => 150000])
                ->assertJsonFragment(['notes' => 'Dental filling performed']);

        $this->assertDatabaseHas('treatments', [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 150000,
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function dentist_can_create_treatment()
    {
        Sanctum::actingAs($this->dentist);

        $treatmentData = [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 350000,
            'notes' => 'Root canal treatment completed',
        ];

        $response = $this->postJson('/api/treatments', $treatmentData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('treatments', [
            'cost' => 350000,
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function treatment_creation_with_prescription()
    {
        Sanctum::actingAs($this->admin);

        $medicine = DentalMedicine::factory()->create();

        $treatmentData = [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 200000,
            'notes' => 'Extraction with medication',
            // Prescription fields
            'medicine_id' => $medicine->id,
            'medication' => 'Amoxicillin',
            'dosage' => '500mg',
            'frequency' => '3 times daily',
            'duration' => '7 days',
            'prescription_amount' => 21,
            'prescription_issue_date' => '2024-12-15',
            'prescription_expiry_date' => '2024-12-22',
            'prescription_instructions' => 'Take with food',
            'max_refills' => 2,
            'prescription_status' => 'active',
            'refill_count' => 0,
        ];

        $response = $this->postJson('/api/treatments', $treatmentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('treatments', [
            'patient_id' => $this->patient->id,
            'medicine_id' => $medicine->id,
            'medication' => 'Amoxicillin',
            'prescription_status' => 'active',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_treatment()
    {
        $treatmentData = [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 100000,
        ];

        $response = $this->postJson('/api/treatments', $treatmentData);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('treatments', [
            'patient_id' => $this->patient->id
        ]);
    }

    /** @test */
    public function treatment_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/treatments', [
            'patient_id' => $this->patient->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['appointment_id', 'cost']);
    }

    /** @test */
    public function admin_can_view_treatments_list()
    {
        Sanctum::actingAs($this->admin);

        // Create some treatments
        Treatment::factory()->count(5)->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id
        ]);

        $response = $this->getJson('/api/treatments');

        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_view_specific_treatment()
    {
        Sanctum::actingAs($this->admin);

        $treatment = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id
        ]);

        $response = $this->getJson("/api/treatments/{$treatment->id}");

        $response->assertStatus(200)
                ->assertJsonFragment(['cost' => $treatment->cost]);
    }

    /** @test */
    public function admin_can_update_treatment()
    {
        Sanctum::actingAs($this->admin);

        $treatment = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id
        ]);

        $updateData = [
            'cost' => 180000,
            'notes' => 'Updated treatment notes',
        ];

        $response = $this->putJson("/api/treatments/{$treatment->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment(['cost' => 180000]);

        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'cost' => 180000,
            'notes' => 'Updated treatment notes'
        ]);
    }

    /** @test */
    public function admin_can_delete_treatment()
    {
        Sanctum::actingAs($this->admin);

        $treatment = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id
        ]);

        $response = $this->deleteJson("/api/treatments/{$treatment->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('treatments', ['id' => $treatment->id]);
    }

    /** @test */
    public function treatment_filter_by_patient_works()
    {
        Sanctum::actingAs($this->admin);

        // Create treatments for different patients
        $otherPatient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);
        
        Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id
        ]);
        
        Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $otherPatient->id,
            'appointment_id' => Appointment::factory()->create(['clinic_id' => $this->clinic->id, 'patient_id' => $otherPatient->id])
        ]);

        $response = $this->getJson("/api/treatments?patient_id={$this->patient->id}");

        $response->assertStatus(200);
        
        $treatments = $response->json('data');
        $this->assertCount(1, $treatments);
        $this->assertEquals($this->patient->id, $treatments[0]['patient_id']);
    }

    /** @test */
    public function treatment_filter_by_cost_range_works()
    {
        Sanctum::actingAs($this->admin);

        // Create treatments with different costs
        Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 50000
        ]);
        
        Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 200000
        ]);

        $response = $this->getJson('/api/treatments?min_cost=100000&max_cost=150000');

        $response->assertStatus(200);
        
        $treatments = $response->json('data');
        $this->assertCount(1, $treatments);
        $this->assertEquals(200000, $treatments[0]['cost']);
    }

    /** @test */
    public function prescription_refill_workflow()
    {
        Sanctum::actingAs($this->admin);

        $medicine = DentalMedicine::factory()->create();

        // Create treatment with prescription
        $treatment = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'medicine_id' => $medicine->id,
            'max_refills' => 3,
            'refill_count' => 0,
            'prescription_status' => 'active'
        ]);

        // 1. Refill prescription
        $refillResponse = $this->postJson("/api/treatments/{$treatment->id}/refill");

        $refillResponse->assertStatus(200);

        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'refill_count' => 1,
            'prescription_status' => 'refilled'
        ]);

        // 2. Refill again
        $secondRefillResponse = $this->postJson("/api/treatments/{$treatment->id}/refill");

        $secondRefillResponse->assertStatus(200);

        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'refill_count' => 2
        ]);

        // 3. Try to refill beyond max_refills (should fail)
        $thirdRefillResponse = $this->postJson("/api/treatments/{$treatment->id}/refill");

        $thirdRefillResponse->assertStatus(422);
    }

    /** @test */
    public function prescription_expiration_workflow()
    {
        Sanctum::actingAs($this->admin);

        $medicine = DentalMedicine::factory()->create();

        // Create treatment with expired prescription
        $treatment = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'medicine_id' => $medicine->id,
            'prescription_expiry_date' => now()->subDays(1),
            'prescription_status' => 'active'
        ]);

        // Try to refill expired prescription (should fail)
        $response = $this->postJson("/api/treatments/{$treatment->id}/refill");

        $response->assertStatus(422);

        // Check that prescription is marked as expired
        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'prescription_status' => 'expired'
        ]);
    }

    /** @test */
    public function treatment_workflow_from_creation_to_completion()
    {
        Sanctum::actingAs($this->admin);

        $medicine = DentalMedicine::factory()->create();

        // 1. Create treatment with prescription
        $treatmentData = [
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 250000,
            'notes' => 'Root canal treatment',
            'medicine_id' => $medicine->id,
            'medication' => 'Amoxicillin',
            'dosage' => '500mg',
            'frequency' => '3 times daily',
            'duration' => '7 days',
            'prescription_amount' => 21,
            'prescription_issue_date' => now()->toDateString(),
            'prescription_expiry_date' => now()->addMonths(3)->toDateString(),
            'prescription_instructions' => 'Take with food',
            'max_refills' => 2,
            'prescription_status' => 'active',
            'refill_count' => 0,
        ];

        $createResponse = $this->postJson('/api/treatments', $treatmentData);
        $createResponse->assertStatus(201);
        
        $treatmentId = $createResponse->json('id');
        $this->assertNotNull($treatmentId);

        // 2. View treatment
        $viewResponse = $this->getJson("/api/treatments/{$treatmentId}");
        $viewResponse->assertStatus(200)
                    ->assertJsonFragment(['cost' => 250000])
                    ->assertJsonFragment(['prescription_status' => 'active']);

        // 3. Update treatment notes
        $updateResponse = $this->putJson("/api/treatments/{$treatmentId}", [
            'notes' => 'Root canal treatment completed successfully'
        ]);
        $updateResponse->assertStatus(200)
                      ->assertJsonFragment(['notes' => 'Root canal treatment completed successfully']);

        // 4. Refill prescription
        $refillResponse = $this->postJson("/api/treatments/{$treatmentId}/refill");
        $refillResponse->assertStatus(200);

        // 5. Verify final state
        $this->assertDatabaseHas('treatments', [
            'id' => $treatmentId,
            'cost' => 250000,
            'notes' => 'Root canal treatment completed successfully',
            'refill_count' => 1,
            'prescription_status' => 'refilled'
        ]);
    }

    /** @test */
    public function treatment_cost_calculation_works()
    {
        Sanctum::actingAs($this->admin);

        // Create treatments with different costs
        $treatment1 = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 150000
        ]);

        $treatment2 = Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'cost' => 200000
        ]);

        // Get total cost for patient
        $response = $this->getJson("/api/patients/{$this->patient->id}/treatments/total-cost");

        $response->assertStatus(200)
                ->assertJsonFragment(['total_cost' => 350000]);
    }

    /** @test */
    public function treatment_search_works()
    {
        Sanctum::actingAs($this->admin);

        // Create treatments with different notes
        Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'notes' => 'Root canal treatment performed'
        ]);

        Treatment::factory()->create([
            'clinic_id' => $this->clinic->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'notes' => 'Dental cleaning completed'
        ]);

        $response = $this->getJson('/api/treatments?search=root');

        $response->assertStatus(200);
        
        $treatments = $response->json('data');
        $this->assertCount(1, $treatments);
        $this->assertStringContainsString('Root canal', $treatments[0]['notes']);
    }
}
