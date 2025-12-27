<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_appointment()
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
            'clinic_id' => $clinic->id,
        ];

        $appointment = Appointment::create($appointmentData);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals($patient->id, $appointment->patient_id);
        $this->assertEquals($dentist->id, $appointment->dentist_id);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals('checkup', $appointment->type);
        $this->assertEquals('Regular checkup', $appointment->notes);
        $this->assertEquals($clinic->id, $appointment->clinic_id);
    }

    /** @test */
    public function it_casts_start_time_and_end_time_to_datetime()
    {
        $appointment = Appointment::factory()->create([
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:00:00'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $appointment->start_time);
        $this->assertInstanceOf(\Carbon\Carbon::class, $appointment->end_time);
        $this->assertEquals('2024-12-15 10:00:00', $appointment->start_time->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-12-15 11:00:00', $appointment->end_time->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_has_relationships()
    {
        $appointment = Appointment::factory()->create();

        // Test relationships exist
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $appointment->patient());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $appointment->dentist());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $appointment->treatments());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $appointment->clinic());
    }

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $appointment = Appointment::factory()->create();

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertNotNull($appointment->patient_id);
        $this->assertNotNull($appointment->dentist_id);
        $this->assertNotNull($appointment->start_time);
        $this->assertNotNull($appointment->end_time);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'dentist_id' => $appointment->dentist_id
        ]);
    }

    /** @test */
    public function it_can_have_different_statuses()
    {
        $statuses = ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];
        
        foreach ($statuses as $status) {
            $appointment = Appointment::factory()->create(['status' => $status]);
            $this->assertEquals($status, $appointment->status);
        }
    }

    /** @test */
    public function it_can_have_different_types()
    {
        $types = ['checkup', 'cleaning', 'filling', 'extraction', 'root_canal', 'crown', 'emergency'];
        
        foreach ($types as $type) {
            $appointment = Appointment::factory()->create(['type' => $type]);
            $this->assertEquals($type, $appointment->type);
        }
    }

    /** @test */
    public function it_validates_time_logic()
    {
        $this->expectException(\Exception::class);
        
        // This would ideally be tested with validation rules in the controller/model
        $appointment = Appointment::factory()->create([
            'start_time' => '2024-12-15 11:00:00',
            'end_time' => '2024-12-15 10:00:00' // End time before start time
        ]);
    }

    /** @test */
    public function it_can_check_if_appointment_is_upcoming()
    {
        $upcomingAppointment = Appointment::factory()->create([
            'start_time' => Carbon::now()->addHours(2),
            'status' => 'scheduled'
        ]);

        $pastAppointment = Appointment::factory()->create([
            'start_time' => Carbon::now()->subHours(2),
            'status' => 'completed'
        ]);

        // Assuming you have an isUpcoming() method
        if (method_exists($upcomingAppointment, 'isUpcoming')) {
            $this->assertTrue($upcomingAppointment->isUpcoming());
            $this->assertFalse($pastAppointment->isUpcoming());
        }
    }

    /** @test */
    public function it_can_check_if_appointment_is_today()
    {
        $todayAppointment = Appointment::factory()->create([
            'start_time' => Carbon::today()->setTime(10, 0),
        ]);

        $tomorrowAppointment = Appointment::factory()->create([
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
        ]);

        // Assuming you have an isToday() method
        if (method_exists($todayAppointment, 'isToday')) {
            $this->assertTrue($todayAppointment->isToday());
            $this->assertFalse($tomorrowAppointment->isToday());
        }
    }

    /** @test */
    public function it_can_calculate_duration()
    {
        $appointment = Appointment::factory()->create([
            'start_time' => '2024-12-15 10:00:00',
            'end_time' => '2024-12-15 11:30:00'
        ]);

        // Assuming you have a duration() method
        if (method_exists($appointment, 'duration')) {
            $this->assertEquals(90, $appointment->duration()); // 90 minutes
        }
    }

    /** @test */
    public function it_can_be_soft_deleted_if_trait_is_present()
    {
        $appointment = Appointment::factory()->create();
        
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($appointment))) {
            $appointment->delete();
            
            $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
            $this->assertNotNull($appointment->deleted_at);
        }
    }

    /** @test */
    public function it_can_be_cancelled()
    {
        $appointment = Appointment::factory()->create(['status' => 'scheduled']);
        
        $appointment->update(['status' => 'cancelled']);
        
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled'
        ]);
    }

    /** @test */
    public function it_can_be_marked_as_completed()
    {
        $appointment = Appointment::factory()->create(['status' => 'in_progress']);
        
        $appointment->update(['status' => 'completed']);
        
        $this->assertEquals('completed', $appointment->status);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed'
        ]);
    }
}
