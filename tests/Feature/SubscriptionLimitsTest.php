<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_plan_user_limit()
    {
        $clinic = Clinic::factory()->basic()->create([
            'subscription_limits' => ['users' => 5]
        ]);

        // Create 5 users (limit)
        User::factory()->count(5)->create(['clinic_id' => $clinic->id]);

        // Try to create 6th user
        $user = User::factory()->make(['clinic_id' => $clinic->id]);
        
        $this->expectException(\Exception::class);
        $user->save();
    }

    public function test_basic_plan_patient_limit()
    {
        $clinic = Clinic::factory()->basic()->create([
            'subscription_limits' => ['patients' => 500]
        ]);

        // Create 500 patients (limit)
        Patient::factory()->count(500)->create(['clinic_id' => $clinic->id]);

        // Try to create 501st patient
        $patient = Patient::factory()->make(['clinic_id' => $clinic->id]);
        
        $this->expectException(\Exception::class);
        $patient->save();
    }

    public function test_pro_plan_higher_limits()
    {
        $clinic = Clinic::factory()->pro()->create([
            'subscription_limits' => ['users' => 15, 'patients' => 2000]
        ]);

        // Should be able to create more than basic plan
        User::factory()->count(10)->create(['clinic_id' => $clinic->id]);
        Patient::factory()->count(1000)->create(['clinic_id' => $clinic->id]);

        $this->assertEquals(10, $clinic->users()->count());
        $this->assertEquals(1000, $clinic->patients()->count());
    }

    public function test_enterprise_plan_unlimited()
    {
        $clinic = Clinic::factory()->enterprise()->create([
            'subscription_limits' => ['users' => 0, 'patients' => 0] // 0 means unlimited
        ]);

        // Should be able to create unlimited users and patients
        User::factory()->count(50)->create(['clinic_id' => $clinic->id]);
        Patient::factory()->count(5000)->create(['clinic_id' => $clinic->id]);

        $this->assertEquals(50, $clinic->users()->count());
        $this->assertEquals(5000, $clinic->patients()->count());
    }
}
