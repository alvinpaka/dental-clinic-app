<?php

namespace Tests\Unit;

use App\Services\AuditService;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_log_basic_audit_entry()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $auditLog = AuditService::log(
            'patient_created',
            $patient,
            null,
            ['name' => $patient->name, 'email' => $patient->email],
            $user
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals($patient->getMorphClass(), $auditLog->subject_type);
        $this->assertEquals($patient->id, $auditLog->subject_id);
        $this->assertEquals('patient_created', $auditLog->action);
        $this->assertNull($auditLog->old_values);
        $this->assertEquals(['name' => $patient->name, 'email' => $patient->email], $auditLog->new_values);
    }

    /** @test */
    public function it_can_log_audit_entry_with_old_and_new_values()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $oldValues = ['name' => 'Old Name', 'phone' => '123-456-7890'];
        $newValues = ['name' => 'New Name', 'phone' => '098-765-4321'];

        $auditLog = AuditService::log(
            'patient_updated',
            $patient,
            $oldValues,
            $newValues,
            $user
        );

        $this->assertEquals($oldValues, $auditLog->old_values);
        $this->assertEquals($newValues, $auditLog->new_values);
        $this->assertEquals('patient_updated', $auditLog->action);
    }

    /** @test */
    public function it_can_log_audit_entry_without_subject()
    {
        $user = User::factory()->create();

        $auditLog = AuditService::log(
            'system_backup',
            null,
            null,
            ['backup_size' => '1.2GB'],
            $user
        );

        $this->assertNull($auditLog->subject_type);
        $this->assertNull($auditLog->subject_id);
        $this->assertEquals('system_backup', $auditLog->action);
        $this->assertEquals(['backup_size' => '1.2GB'], $auditLog->new_values);
    }

    /** @test */
    public function it_can_log_audit_entry_without_user()
    {
        $patient = Patient::factory()->create();

        $auditLog = AuditService::log(
            'patient_deleted',
            $patient,
            ['name' => $patient->name],
            null,
            null
        );

        $this->assertNull($auditLog->user_id);
        $this->assertEquals('patient_deleted', $auditLog->action);
        $this->assertEquals(['name' => $patient->name], $auditLog->old_values);
    }

    /** @test */
    public function it_can_log_login_event()
    {
        $user = User::factory()->create();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('ip')->andReturn('192.168.1.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (Test Browser)');

        $auditLog = AuditService::logLogin($user, $request);

        $this->assertEquals('login', $auditLog->action);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals($user->getMorphClass(), $auditLog->subject_type);
        $this->assertEquals($user->id, $auditLog->subject_id);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Mozilla/5.0 (Test Browser)', $auditLog->user_agent);
    }

    /** @test */
    public function it_can_log_logout_event()
    {
        $user = User::factory()->create();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('ip')->andReturn('192.168.1.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0 (Test Browser)');

        $auditLog = AuditService::logLogout($user, $request);

        $this->assertEquals('logout', $auditLog->action);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals($user->getMorphClass(), $auditLog->subject_type);
        $this->assertEquals($user->id, $auditLog->subject_id);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Mozilla/5.0 (Test Browser)', $auditLog->user_agent);
    }

    /** @test */
    public function it_uses_authenticated_user_when_no_user_provided()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        
        // Mock the auth facade
        $this->actingAs($user);

        $auditLog = AuditService::log(
            'patient_viewed',
            $patient,
            null,
            ['viewed_at' => now()],
            null // No user provided, should use authenticated user
        );

        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals('patient_viewed', $auditLog->action);
    }

    /** @test */
    public function it_uses_current_request_when_no_request_provided()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        // This will use the current test request
        $auditLog = AuditService::log(
            'patient_created',
            $patient,
            null,
            ['name' => $patient->name],
            $user,
            null // No request provided, should use current request
        );

        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertNotNull($auditLog->ip_address);
    }

    /** @test */
    public function it_stores_audit_log_in_database()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        AuditService::log(
            'patient_updated',
            $patient,
            ['status' => 'inactive'],
            ['status' => 'active'],
            $user
        );

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'subject_type' => $patient->getMorphClass(),
            'subject_id' => $patient->id,
            'action' => 'patient_updated',
        ]);
    }

    /** @test */
    public function it_handles_null_values_gracefully()
    {
        $auditLog = AuditService::log(
            'system_event',
            null,
            null,
            null,
            null,
            null
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNull($auditLog->user_id);
        $this->assertNull($auditLog->subject_type);
        $this->assertNull($auditLog->subject_id);
        $this->assertNull($auditLog->old_values);
        $this->assertNull($auditLog->new_values);
        $this->assertEquals('system_event', $auditLog->action);
    }

    /** @test */
    public function it_can_log_complex_data_structures()
    {
        $user = User::factory()->create();
        
        $complexData = [
            'patient_info' => [
                'name' => 'John Doe',
                'contacts' => ['email' => 'john@example.com', 'phone' => '+1234567890'],
                'medical_history' => ['conditions' => ['hypertension'], 'allergies' => ['penicillin']]
            ],
            'appointment_details' => [
                'scheduled_at' => '2024-12-15 10:00:00',
                'duration' => 60,
                'type' => 'checkup'
            ]
        ];

        $auditLog = AuditService::log(
            'complex_patient_update',
            null,
            null,
            $complexData,
            $user
        );

        $this->assertEquals($complexData, $auditLog->new_values);
        $this->assertEquals('complex_patient_update', $auditLog->action);
    }

    /** @test */
    public function it_tracks_user_clinic_id()
    {
        $user = User::factory()->create(['clinic_id' => 123]);
        $patient = Patient::factory()->create();

        $auditLog = AuditService::log(
            'patient_created',
            $patient,
            null,
            ['name' => $patient->name],
            $user
        );

        $this->assertEquals(123, $auditLog->clinic_id);
    }

    /** @test */
    public function it_can_log_model_deletion()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $auditLog = AuditService::log(
            'patient_deleted',
            $patient,
            ['name' => $patient->name, 'email' => $patient->email],
            null,
            $user
        );

        $this->assertEquals('patient_deleted', $auditLog->action);
        $this->assertEquals(['name' => $patient->name, 'email' => $patient->email], $auditLog->old_values);
        $this->assertNull($auditLog->new_values);
    }
}
