<?php

namespace Tests\Feature;

use App\Services\GeminiService;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AiChatWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Clinic $clinic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        $this->user = User::factory()->create(['clinic_id' => $this->clinic->id]);
        $this->user->assignRole('receptionist');
    }

    /** @test */
    public function user_can_send_chat_message()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Hello! I can help you with patient management and appointment booking. How can I assist you today?'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello, I need help with the clinic'
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'Hello! I can help you with patient management and appointment booking. How can I assist you today?'
                ]);
    }

    /** @test */
    public function ai_can_extract_patient_creation_info()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "create_patient", "data": {"name": "John Doe", "email": "john@example.com", "phone": "+1234567890", "age": 30}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'I want to create a new patient. His name is John Doe, email is john@example.com, phone is +1234567890, and he\'s 30 years old.'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'action',
                    'data'
                ])
                ->assertJsonFragment([
                    'action' => 'create_patient'
                ]);
    }

    /** @test */
    public function ai_can_extract_appointment_booking_info()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "book_appointment", "data": {"patient_name": "Jane Smith", "date": "2024-12-16", "time": "14:00", "type": "checkup"}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'I want to book an appointment for Jane Smith tomorrow at 2pm for a checkup.'
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'action' => 'book_appointment',
                    'data' => [
                        'patient_name' => 'Jane Smith',
                        'date' => '2024-12-16',
                        'time' => '14:00',
                        'type' => 'checkup'
                    ]
                ]);
    }

    /** @test */
    public function ai_can_request_missing_information()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "need_more_info", "data": {"missing_fields": ["email", "phone"], "message": "I need the patient\'s email and phone number to create a new patient record."}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Create a patient named John'
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'action' => 'need_more_info'
                ]);
    }

    /** @test */
    public function user_can_confirm_patient_creation()
    {
        Sanctum::actingAs($this->user);

        $patientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'dob' => '1994-12-27', // 30 years old
            'address' => '123 Main St'
        ];

        $response = $this->postJson('/api/chat/patient', $patientData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ]);

        $this->assertDatabaseHas('patients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function user_can_confirm_appointment_booking()
    {
        Sanctum::actingAs($this->user);

        // Create a patient first
        $patient = Patient::factory()->create(['clinic_id' => $this->clinic->id]);

        $appointmentData = [
            'patient_name' => $patient->name,
            'date' => '2024-12-16',
            'time' => '14:00',
            'type' => 'checkup'
        ];

        $response = $this->postJson('/api/chat/appointment', $appointmentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'patient_id',
                    'start_time',
                    'type',
                    'status'
                ]);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'type' => 'checkup',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function appointment_booking_handles_patient_not_found()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'patient_name' => 'Nonexistent Patient',
            'date' => '2024-12-16',
            'time' => '14:00',
            'type' => 'checkup'
        ];

        $response = $this->postJson('/api/chat/appointment', $appointmentData);

        $response->assertStatus(404)
                ->assertJsonFragment([
                    'message' => 'Patient not found'
                ]);
    }

    /** @test */
    public function patient_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->user);

        $incompleteData = [
            'name' => 'John Doe',
            // Missing email, phone, dob
        ];

        $response = $this->postJson('/api/chat/patient', $incompleteData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'phone', 'dob']);
    }

    /** @test */
    public function appointment_booking_validates_required_fields()
    {
        Sanctum::actingAs($this->user);

        $incompleteData = [
            'patient_name' => 'John Doe',
            // Missing date, time, type
        ];

        $response = $this->postJson('/api/chat/appointment', $incompleteData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['date', 'time', 'type']);
    }

    /** @test */
    public function ai_handles_conversation_history()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        $mockResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'I remember you asked about creating a patient earlier. Would you like to proceed with that?'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($mockResponse, 200)
        ]);

        $history = [
            ['role' => 'user', 'content' => 'I want to create a patient'],
            ['role' => 'assistant', 'content' => 'I can help you create a patient. What are their details?']
        ];

        $response = $this->postJson('/api/chat', [
            'message' => 'Yes, let\'s continue',
            'history' => $history
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) use ($history) {
            $payload = json_decode($request->body(), true);
            $contents = $payload['contents'] ?? [];
            
            // Should have system instruction + history + new message
            return count($contents) >= 3;
        });
    }

    /** @test */
    public function ai_handles_api_errors_gracefully()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API Error']], 500)
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello'
        ]);

        $response->assertStatus(500)
                ->assertJsonFragment([
                    'message' => 'Failed to process chat message'
                ]);
    }

    /** @test */
    public function ai_handles_missing_api_key()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => null]);
        putenv('GEMINI_API_KEY=');

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello'
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'message' => 'I\'m sorry, I\'m not correctly configured yet. Please contact the administrator.'
                ]);
    }

    /** @test */
    public function complete_patient_creation_workflow()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        // Step 1: User initiates patient creation
        $extractResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "create_patient", "data": {"name": "Alice Johnson", "email": "alice@example.com", "phone": "+1234567890", "age": 25}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($extractResponse, 200)
        ]);

        $initResponse = $this->postJson('/api/chat', [
            'message' => 'Create a patient named Alice Johnson, email alice@example.com, phone +1234567890, age 25'
        ]);

        $initResponse->assertStatus(200)
                    ->assertJsonFragment(['action' => 'create_patient']);

        // Step 2: User confirms patient creation
        $patientData = [
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'phone' => '+1234567890',
            'dob' => '1999-12-27', // 25 years old
            'address' => '456 Oak Ave'
        ];

        $confirmResponse = $this->postJson('/api/chat/patient', $patientData);

        $confirmResponse->assertStatus(201)
                       ->assertJsonFragment(['name' => 'Alice Johnson']);

        // Step 3: Verify patient was created
        $this->assertDatabaseHas('patients', [
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function complete_appointment_booking_workflow()
    {
        Sanctum::actingAs($this->user);

        config(['services.gemini.key' => 'test-api-key']);

        // Step 1: Create a patient first
        $patient = Patient::factory()->create(['clinic_id' => $this->clinic->id, 'name' => 'Bob Smith']);

        // Step 2: User initiates appointment booking
        $extractResponse = [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '{"action": "book_appointment", "data": {"patient_name": "Bob Smith", "date": "2024-12-16", "time": "14:00", "type": "cleaning"}}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($extractResponse, 200)
        ]);

        $initResponse = $this->postJson('/api/chat', [
            'message' => 'Book an appointment for Bob Smith tomorrow at 2pm for a cleaning'
        ]);

        $initResponse->assertStatus(200)
                    ->assertJsonFragment(['action' => 'book_appointment']);

        // Step 3: User confirms appointment booking
        $appointmentData = [
            'patient_name' => 'Bob Smith',
            'date' => '2024-12-16',
            'time' => '14:00',
            'type' => 'cleaning'
        ];

        $confirmResponse = $this->postJson('/api/chat/appointment', $appointmentData);

        $confirmResponse->assertStatus(201)
                       ->assertJsonFragment(['type' => 'cleaning']);

        // Step 4: Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'type' => 'cleaning',
            'clinic_id' => $this->clinic->id
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_chat_endpoints()
    {
        // Test without authentication
        $response = $this->postJson('/api/chat', [
            'message' => 'Hello'
        ]);

        $response->assertStatus(401);

        $response = $this->postJson('/api/chat/patient', [
            'name' => 'Test Patient',
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(401);

        $response = $this->postJson('/api/chat/appointment', [
            'patient_name' => 'Test Patient',
            'date' => '2024-12-16',
            'time' => '14:00',
            'type' => 'checkup'
        ]);

        $response->assertStatus(401);
    }
}
