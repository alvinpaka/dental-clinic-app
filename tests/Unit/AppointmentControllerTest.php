<?php

namespace Tests\Unit;

use App\Http\Controllers\AppointmentController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AppointmentController();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_index_appointments()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $dentist = User::factory()->create();
        
        $appointments = Appointment::factory()->count(5)->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id
        ]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_store_a_new_appointment()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $dentist = User::factory()->create();
        
        $appointmentData = [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00',
            'status' => 'scheduled',
            'type' => 'checkup',
            'notes' => 'Regular checkup',
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn($appointmentData);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn($appointmentData);

        $response = $this->controller->store($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'status' => 'scheduled',
            'type' => 'checkup',
            'clinic_id' => $clinic->id
        ]);
    }

    /** @test */
    public function it_can_show_a_specific_appointment()
    {
        $clinic = Clinic::factory()->create();
        $appointment = Appointment::factory()->create(['clinic_id' => $clinic->id]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $response = $this->controller->show($request, $appointment);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_update_an_appointment()
    {
        $clinic = Clinic::factory()->create();
        $appointment = Appointment::factory()->create(['clinic_id' => $clinic->id]);

        $updateData = [
            'status' => 'confirmed',
            'notes' => 'Patient confirmed appointment',
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn($updateData);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn($updateData);

        $response = $this->controller->update($request, $appointment);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
            'notes' => 'Patient confirmed appointment'
        ]);
    }

    /** @test */
    public function it_can_delete_an_appointment()
    {
        $clinic = Clinic::factory()->create();
        $appointment = Appointment::factory()->create(['clinic_id' => $clinic->id]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $response = $this->controller->destroy($request, $appointment);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    /** @test */
    public function it_validates_appointment_data_on_store()
    {
        $clinic = Clinic::factory()->create();
        
        $invalidData = [
            'patient_id' => '', // Empty patient ID
            'start_time' => 'invalid-date', // Invalid date format
            'end_time' => '2024-12-15 09:00:00', // End before start
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
    public function it_validates_appointment_data_on_update()
    {
        $clinic = Clinic::factory()->create();
        $appointment = Appointment::factory()->create(['clinic_id' => $clinic->id]);
        
        $invalidData = [
            'status' => 'invalid_status', // Invalid status
            'start_time' => 'invalid-date',
        ];

        // Mock the request to trigger validation
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andThrow(new \Illuminate\Validation\ValidationException(
            Mockery::mock(\Illuminate\Contracts\Validation\Validator::class)
        ));
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $this->controller->update($request, $appointment);
    }

    /** @test */
    public function it_prevents_accessing_appointments_from_other_clinics()
    {
        $clinic1 = Clinic::factory()->create();
        $clinic2 = Clinic::factory()->create();
        $appointment = Appointment::factory()->create(['clinic_id' => $clinic1->id]);

        // Mock request with different clinic
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic2->id]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        $this->controller->show($request, $appointment);
    }

    /** @test */
    public function it_can_filter_appointments_by_date()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $dentist = User::factory()->create();
        
        // Create appointments for different dates
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => '2024-12-15 10:00:00'
        ]);
        
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => '2024-12-16 10:00:00'
        ]);

        // Mock request with date filter
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('get')->with('date')->andReturn('2024-12-15');

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_filter_appointments_by_status()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $dentist = User::factory()->create();
        
        // Create appointments with different statuses
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'status' => 'scheduled'
        ]);
        
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'status' => 'completed'
        ]);

        // Mock request with status filter
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('get')->with('status')->andReturn('scheduled');

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_get_todays_appointments()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $dentist = User::factory()->create();
        
        // Create today's appointment
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => Carbon::today()->setTime(10, 0)
        ]);
        
        // Create tomorrow's appointment
        Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 0)
        ]);

        // Mock request for today's appointments
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('get')->with('today')->andReturn('true');

        $response = $this->controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_cancel_an_appointment()
    {
        $clinic = Clinic::factory()->create();
        $appointment = Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'status' => 'scheduled'
        ]);

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn(['status' => 'cancelled']);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn(['status' => 'cancelled']);

        $response = $this->controller->update($request, $appointment);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function it_can_reschedule_an_appointment()
    {
        $clinic = Clinic::factory()->create();
        $appointment = Appointment::factory()->create([
            'clinic_id' => $clinic->id,
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00'
        ]);

        $rescheduleData = [
            'start_time' => '2024-12-15 14:00:00',
            'end_time' => '2024-12-15 15:00:00',
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->andReturn($rescheduleData);
        $request->shouldReceive('user')->andReturn((object)['clinic_id' => $clinic->id]);
        $request->shouldReceive('all')->andReturn($rescheduleData);

        $response = $this->controller->update($request, $appointment);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'start_time' => '2024-12-15 14:00:00',
            'end_time' => '2024-12-15 15:00:00'
        ]);
    }
}
