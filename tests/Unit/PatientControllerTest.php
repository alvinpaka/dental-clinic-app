<?php

namespace Tests\Unit;

use App\Http\Controllers\PatientController;
use App\Models\Patient;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Mockery;

class PatientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PatientController();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_index_patients()
    {
        $clinic = Clinic::factory()->create();
        $patients = Patient::factory()->count(5)->create(['clinic_id' => $clinic->id]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        // Test the index method
        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_store_a_new_patient()
    {
        $clinic = Clinic::factory()->create();
        
        $patientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'dob' => '1990-01-01',
            'address' => '123 Main St',
            'medical_history' => 'No significant medical history',
            'allergies' => ['Penicillin'],
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn($patientData);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn($patientData);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $this->assertDatabaseHas('patients', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'clinic_id' => $clinic->id
        ]);
    }

    /** @test */
    public function it_can_show_a_specific_patient()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $response = $this->controller->show($request, $patient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_update_a_patient()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+0987654321',
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn($updateData);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn($updateData);

        $response = $this->controller->update($request, $patient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+0987654321'
        ]);
    }

    /** @test */
    public function it_can_delete_a_patient()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $response = $this->controller->destroy($request, $patient);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    /** @test */
    public function it_validates_patient_data_on_store()
    {
        $clinic = Clinic::factory()->create();
        
        $invalidData = [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email
            'phone' => '', // Empty phone
        ];

        // Mock the request to trigger validation
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andThrow(new \Illuminate\Validation\ValidationException(
            Mockery::mock(\Illuminate\Contracts\Validation\Validator::class)
        ));
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $this->controller->store($request);
    }

    /** @test */
    public function it_validates_patient_data_on_update()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        
        $invalidData = [
            'name' => '', // Empty name
            'email' => 'invalid-email', // Invalid email
        ];

        // Mock the request to trigger validation
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andThrow(new \Illuminate\Validation\ValidationException(
            Mockery::mock(\Illuminate\Contracts\Validation\Validator::class)
        ));
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $this->controller->update($request, $patient);
    }

    /** @test */
    public function it_prevents_accessing_patients_from_other_clinics()
    {
        $clinic1 = Clinic::factory()->create();
        $clinic2 = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic1->id]);

        // Mock request with different clinic
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic2->id]);

        // This should ideally throw an authorization exception
        // depending on how the clinic scope is implemented
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        $this->controller->show($request, $patient);
    }

    /** @test */
    public function it_can_search_patients()
    {
        $clinic = Clinic::factory()->create();
        $patient1 = Patient::factory()->create(['name' => 'John Smith', 'clinic_id' => $clinic->id]);
        $patient2 = Patient::factory()->create(['name' => 'Jane Doe', 'clinic_id' => $clinic->id]);
        $patient3 = Patient::factory()->create(['name' => 'John Johnson', 'clinic_id' => $clinic->id]);

        // Mock request with search parameter
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('get')->with('search')->andReturn('John');

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_paginate_patients()
    {
        $clinic = Clinic::factory()->create();
        Patient::factory()->count(25)->create(['clinic_id' => $clinic->id]);

        // Mock request with pagination
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('get')->with('per_page')->andReturn(10);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_patient_creation_with_allergies()
    {
        $clinic = Clinic::factory()->create();
        
        $patientData = [
            'name' => 'Allergic Patient',
            'email' => 'allergic@example.com',
            'phone' => '+1234567890',
            'dob' => '1990-01-01',
            'address' => '123 Main St',
            'medical_history' => 'No significant medical history',
            'allergies' => ['Penicillin', 'Latex', 'Nuts'],
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn($patientData);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn($patientData);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $this->assertDatabaseHas('patients', [
            'name' => 'Allergic Patient',
            'email' => 'allergic@example.com',
            'clinic_id' => $clinic->id
        ]);
    }
}
