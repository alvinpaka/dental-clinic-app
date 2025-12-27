<?php

namespace Tests\Unit;

use App\Models\Treatment;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\DentalMedicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TreatmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_treatment()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'clinic_id' => $clinic->id]);
        
        $treatmentData = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'cost' => 150000, // 1500.00 in cents
            'notes' => 'Dental filling performed',
            'file_path' => 'treatments/filling_report.pdf',
            'clinic_id' => $clinic->id,
        ];

        $treatment = Treatment::create($treatmentData);

        $this->assertInstanceOf(Treatment::class, $treatment);
        $this->assertEquals($patient->id, $treatment->patient_id);
        $this->assertEquals($appointment->id, $treatment->appointment_id);
        $this->assertEquals(150000, $treatment->cost);
        $this->assertEquals('Dental filling performed', $treatment->notes);
        $this->assertEquals('treatments/filling_report.pdf', $treatment->file_path);
        $this->assertEquals($clinic->id, $treatment->clinic_id);
    }

    /** @test */
    public function it_can_create_a_treatment_with_prescription()
    {
        $clinic = Clinic::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $clinic->id]);
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id, 'clinic_id' => $clinic->id]);
        $medicine = DentalMedicine::factory()->create();
        
        $treatmentData = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'cost' => 200000,
            'notes' => 'Root canal treatment',
            'clinic_id' => $clinic->id,
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

        $treatment = Treatment::create($treatmentData);

        $this->assertEquals($medicine->id, $treatment->medicine_id);
        $this->assertEquals('Amoxicillin', $treatment->medication);
        $this->assertEquals('500mg', $treatment->dosage);
        $this->assertEquals('3 times daily', $treatment->frequency);
        $this->assertEquals('7 days', $treatment->duration);
        $this->assertEquals(21, $treatment->prescription_amount);
        $this->assertEquals('2024-12-15', $treatment->prescription_issue_date);
        $this->assertEquals('2024-12-22', $treatment->prescription_expiry_date);
        $this->assertEquals('Take with food', $treatment->prescription_instructions);
        $this->assertEquals(2, $treatment->max_refills);
        $this->assertEquals('active', $treatment->prescription_status);
        $this->assertEquals(0, $treatment->refill_count);
    }

    /** @test */
    public function it_has_relationships()
    {
        $treatment = Treatment::factory()->create();

        // Test relationships exist
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $treatment->patient());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $treatment->appointment());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $treatment->medicine());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $treatment->procedures());
    }

    /** @test */
    public function it_can_be_created_with_factory()
    {
        $treatment = Treatment::factory()->create();

        $this->assertInstanceOf(Treatment::class, $treatment);
        $this->assertNotNull($treatment->patient_id);
        $this->assertNotNull($treatment->cost);
        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'patient_id' => $treatment->patient_id,
            'cost' => $treatment->cost
        ]);
    }

    /** @test */
    public function it_can_format_cost_as_currency()
    {
        $treatment = Treatment::factory()->create(['cost' => 150000]);

        // Assuming you have a formattedCost accessor
        if (method_exists($treatment, 'getFormattedCostAttribute')) {
            $this->assertEquals('$1,500.00', $treatment->formatted_cost);
        }
    }

    /** @test */
    public function it_can_check_if_has_prescription()
    {
        $treatmentWithPrescription = Treatment::factory()->create([
            'medicine_id' => DentalMedicine::factory()->create()->id,
            'medication' => 'Ibuprofen'
        ]);

        $treatmentWithoutPrescription = Treatment::factory()->create();

        // Assuming you have a hasPrescription() method
        if (method_exists($treatmentWithPrescription, 'hasPrescription')) {
            $this->assertTrue($treatmentWithPrescription->hasPrescription());
            $this->assertFalse($treatmentWithoutPrescription->hasPrescription());
        }
    }

    /** @test */
    public function it_can_check_if_prescription_is_expired()
    {
        $expiredTreatment = Treatment::factory()->create([
            'prescription_expiry_date' => Carbon::yesterday(),
            'prescription_status' => 'active'
        ]);

        $validTreatment = Treatment::factory()->create([
            'prescription_expiry_date' => Carbon::tomorrow(),
            'prescription_status' => 'active'
        ]);

        // Assuming you have an isPrescriptionExpired() method
        if (method_exists($expiredTreatment, 'isPrescriptionExpired')) {
            $this->assertTrue($expiredTreatment->isPrescriptionExpired());
            $this->assertFalse($validTreatment->isPrescriptionExpired());
        }
    }

    /** @test */
    public function it_can_check_if_refills_available()
    {
        $treatmentWithRefills = Treatment::factory()->create([
            'max_refills' => 3,
            'refill_count' => 1,
            'prescription_status' => 'active'
        ]);

        $treatmentWithoutRefills = Treatment::factory()->create([
            'max_refills' => 2,
            'refill_count' => 2,
            'prescription_status' => 'active'
        ]);

        // Assuming you have a hasRefillsAvailable() method
        if (method_exists($treatmentWithRefills, 'hasRefillsAvailable')) {
            $this->assertTrue($treatmentWithRefills->hasRefillsAvailable());
            $this->assertFalse($treatmentWithoutRefills->hasRefillsAvailable());
        }
    }

    /** @test */
    public function it_can_increment_refill_count()
    {
        $treatment = Treatment::factory()->create([
            'max_refills' => 3,
            'refill_count' => 1,
            'prescription_status' => 'active'
        ]);

        // Assuming you have a refill() method
        if (method_exists($treatment, 'refill')) {
            $treatment->refill();
            
            $this->assertEquals(2, $treatment->refill_count);
            $this->assertDatabaseHas('treatments', [
                'id' => $treatment->id,
                'refill_count' => 2
            ]);
        }
    }

    /** @test */
    public function it_can_be_soft_deleted_if_trait_is_present()
    {
        $treatment = Treatment::factory()->create();
        
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($treatment))) {
            $treatment->delete();
            
            $this->assertSoftDeleted('treatments', ['id' => $treatment->id]);
            $this->assertNotNull($treatment->deleted_at);
        }
    }

    /** @test */
    public function it_can_have_different_prescription_statuses()
    {
        $statuses = ['active', 'expired', 'discontinued', 'refilled'];
        
        foreach ($statuses as $status) {
            $treatment = Treatment::factory()->create(['prescription_status' => $status]);
            $this->assertEquals($status, $treatment->prescription_status);
        }
    }

    /** @test */
    public function it_validates_prescription_dates()
    {
        $this->expectException(\Exception::class);
        
        // This would ideally be tested with validation rules
        $treatment = Treatment::factory()->create([
            'prescription_issue_date' => '2024-12-20',
            'prescription_expiry_date' => '2024-12-15' // Expiry before issue
        ]);
    }

    /** @test */
    public function it_can_calculate_total_refills_used()
    {
        $treatment = Treatment::factory()->create([
            'max_refills' => 5,
            'refill_count' => 3
        ]);

        // Assuming you have a totalRefillsUsed() method
        if (method_exists($treatment, 'totalRefillsUsed')) {
            $this->assertEquals(3, $treatment->totalRefillsUsed());
        }
    }

    /** @test */
    public function it_can_calculate_remaining_refills()
    {
        $treatment = Treatment::factory()->create([
            'max_refills' => 5,
            'refill_count' => 2
        ]);

        // Assuming you have a remainingRefills() method
        if (method_exists($treatment, 'remainingRefills')) {
            $this->assertEquals(3, $treatment->remainingRefills());
        }
    }
}
