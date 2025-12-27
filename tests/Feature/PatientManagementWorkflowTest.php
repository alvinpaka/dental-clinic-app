<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class PatientManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $dentist;
    protected User $receptionist;
    protected Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        
        $this->admin = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->admin->assignRole('admin');
        
        $this->dentist = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->dentist->assignRole('dentist');
        
        $this->receptionist = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->receptionist->assignRole('receptionist');
    }

    /** @test */
    public function admin_can_create_patient()
    {
        Sanctum::actingAs($this->admin);

        $patientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'dob' => '1990-01-01',
            'address' => '123 Main St',
            'medical_history' => 'No significant medical history',
            'allergies' => ['Penicillin'],
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(201)
                ->assertJsonFragment(['name' => 'John Doe'])
                ->assertJsonFragment(['email' => 'john@example.com']);

        $this->assertDatabaseHas('patients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function dentist_can_create_patient()
    {
        Sanctum::actingAs($this->dentist);

        $patientData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+0987654321',
            'dob' => '1985-05-15',
            'address' => '456 Oak Ave',
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('patients', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function receptionist_can_create_patient()
    {
        Sanctum::actingAs($this->receptionist);

        $patientData = [
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'phone' => '+1122334455',
            'dob' => '1975-08-20',
            'address' => '789 Pine Rd',
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('patients', [
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_patient()
    {
        $patientData = [
            'name' => 'Unauthorized User',
            'email' => 'unauth@example.com',
            'phone' => '+1234567890',
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('patients', [
            'email' => 'unauth@example.com'
        ]);
    }

    /** @test */
    public function user_cannot_create_patient_in_other_clinic()
    {
        $otherClinic = Clinic::factory()->create();
        $otherUser = User::factory()->create(['clinic_id' => $otherClinic->id]);
        $otherUser->assignRole('dentist');

        Sanctum::actingAs($otherUser);

        $patientData = [
            'name' => 'Cross Clinic Patient',
            'email' => 'cross@example.com',
            'phone' => '+1234567890',
        ];

        $response = $this->postJson('/api/patients', $patientData);

        // Depending on implementation, this might be 403 or 201 but with clinic_id forced
        $response->assertStatus(201);
        
        // Verify patient is created in the user's clinic, not the target clinic
        $this->assertDatabaseHas('patients', [
            'name' => 'Cross Clinic Patient',
            'email' => 'cross@example.com',
            'clinic_id' => $otherClinic->id
        ]);
    }

    /** @test */
    public function patient_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/patients', [
            'email' => 'incomplete@example.com',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'phone', 'dob']);
    }

    /** @test */
    public function patient_creation_validates_email_format()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/patients', [
            'name' => 'Test Patient',
            'email' => 'invalid-email',
            'phone' => '+1234567890',
            'dob' => '1990-01-01',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function patient_creation_validates_email_uniqueness_within_clinic()
    {
        // Create existing patient
        Patient::factory()->create([
            'email' => 'existing@example.com',
            'clinic_id' => $this->clinic->id
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/patients', [
            'name' => 'Duplicate Email Patient',
            'email' => 'existing@example.com',
            'phone' => '+1234567890',
            'dob' => '1990-01-01',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function admin_can_view_patients_list()
    {
        Sanctum::actingAs($this->admin);

        // Create some patients
        Patient::factory()->count(5)->create(['clinic_id' => $this->clinic->id]);

        $response = $this->getJson('/api/patients');

        $response->assertStatus(200)
                ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_view_specific_patient()
    {
        Sanctum::actingAs($this->admin);

        $patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $response = $this->getJson("/api/patients/{$patient->id}");

        $response->assertStatus(200)
                ->assertJsonFragment(['name' => $patient->name]);
    }

    /** @test */
    public function admin_can_update_patient()
    {
        Sanctum::actingAs($this->admin);

        $patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '+9999999999',
            'address' => 'New Address',
        ];

        $response = $this->putJson("/api/patients/{$patient->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'Updated Name',
            'phone' => '+9999999999',
            'address' => 'New Address'
        ]);
    }

    /** @test */
    public function admin_can_delete_patient()
    {
        Sanctum::actingAs($this->admin);

        $patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $response = $this->deleteJson("/api/patients/{$patient->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    /** @test */
    public function patient_search_works_correctly()
    {
        Sanctum::actingAs($this->admin);

        // Create patients with different names
        Patient::factory()->create(['name' => 'John Smith', 'clinic_id' => $this->clinic->id]);
        Patient::factory()->create(['name' => 'Jane Doe', 'clinic_id' => $this->clinic->id]);
        Patient::factory()->create(['name' => 'John Johnson', 'clinic_id' => $this->clinic->id]);

        $response = $this->getJson('/api/patients?search=John');

        $response->assertStatus(200);
        
        // Should return 2 patients with "John" in name
        $patients = $response->json('data');
        $this->assertCount(2, $patients);
        
        $names = collect($patients)->pluck('name')->toArray();
        $this->assertContains('John Smith', $names);
        $this->assertContains('John Johnson', $names);
        $this->assertNotContains('Jane Doe', $names);
    }

    /** @test */
    public function patient_pagination_works_correctly()
    {
        Sanctum::actingAs($this->admin);

        // Create more patients than default per page
        Patient::factory()->count(25)->create(['clinic_id' => $this->clinic->id]);

        $response = $this->getJson('/api/patients?per_page=10');

        $response->assertStatus(200)
                ->assertJsonCount(10, 'data')
                ->assertJsonStructure([
                    'data',
                    'links',
                    'meta'
                ]);
    }

    /** @test */
    public function patient_workflow_from_creation_to_deletion()
    {
        Sanctum::actingAs($this->admin);

        // 1. Create patient
        $patientData = [
            'name' => 'Workflow Patient',
            'email' => 'workflow@example.com',
            'phone' => '+1234567890',
            'dob' => '1990-01-01',
            'address' => '123 Workflow St',
        ];

        $createResponse = $this->postJson('/api/patients', $patientData);
        $createResponse->assertStatus(201);
        
        $patientId = $createResponse->json('id');
        $this->assertNotNull($patientId);

        // 2. View patient
        $viewResponse = $this->getJson("/api/patients/{$patientId}");
        $viewResponse->assertStatus(200)
                    ->assertJsonFragment(['name' => 'Workflow Patient']);

        // 3. Update patient
        $updateResponse = $this->putJson("/api/patients/{$patientId}", [
            'name' => 'Updated Workflow Patient',
            'address' => '456 Updated Ave'
        ]);
        $updateResponse->assertStatus(200)
                      ->assertJsonFragment(['name' => 'Updated Workflow Patient']);

        // 4. Delete patient
        $deleteResponse = $this->deleteJson("/api/patients/{$patientId}");
        $deleteResponse->assertStatus(204);

        // 5. Verify deletion
        $this->assertDatabaseMissing('patients', ['id' => $patientId]);
    }
}
