<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PtAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $perms = ['clinical.view', 'clinical.assess']): User
    {
        $u = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        foreach ($perms as $p) $u->permissionOverrides()->create(['permission' => $p, 'allowed' => true]);
        return $u->fresh();
    }

    private function patient(): Patient
    {
        return Patient::create(['patient_number' => 'PT-001', 'full_name' => 'John Mwanga', 'sex' => 'male', 'date_of_birth' => '1985-03-10', 'phone' => '0712', 'address' => 'Dar', 'occupation' => 'Teacher', 'religion' => 'Christian', 'tribe' => 'Chagga', 'status' => 'active', 'created_by' => 1]);
    }

    public function test_requires_permission(): void
    {
        $u = $this->staff([]);
        $p = $this->patient();
        $this->actingAs($u)->get(route('assessments.create', $p))->assertForbidden(); // clinical.view is a CLINIC_STAFF role default — index OK, create denied
        $this->actingAs($u)->get(route('assessments.index', $p))->assertOk();
    }

    public function test_create_full_assessment(): void
    {
        $u = $this->staff();
        $p = $this->patient();
        $this->actingAs($u)->post(route('assessments.store', $p), [
            'chief_complaint' => 'Lower back pain x 2 weeks',
            'medical_diagnosis' => 'Lumbar strain',
            'hpi' => 'Gradual onset after lifting',
            'vitals' => ['bp' => '120/80', 'hr' => '72', 'temp' => '36.8'],
            'local_assessment' => 'Tenderness L4-L5',
            'pt_impression' => 'Mechanical low back pain',
            'treatment_aim' => 'Reduce pain, restore function',
            'short_term_plan' => '3 sessions/week',
            'home_visit' => '1',
            'home_visit_notes' => 'Twice weekly home program',
            'prescribed_equipment' => 'Lumbar support belt',
        ])->assertRedirect();
        $a = PtAssessment::firstOrFail();
        $this->assertSame('Lumbar strain', $a->medical_diagnosis);
        $this->assertSame('120/80', $a->vitals['bp']);
        $this->assertTrue($a->home_visit);
        $this->assertSame($u->id, $a->assessed_by);
        $this->assertNotNull($a->reference);
    }

    public function test_view_and_print(): void
    {
        $u = $this->staff(['clinical.view']);
        $a = PtAssessment::create(['reference' => 'PTA-TEST1', 'patient_id' => $this->patient()->id, 'assessed_by' => $u->id, 'chief_complaint' => 'Knee pain']);
        $this->actingAs($u)->get(route('assessments.show', $a))->assertOk();
        $this->actingAs($u)->get(route('assessments.print', $a))->assertOk();
    }

    public function test_update(): void
    {
        $u = $this->staff();
        $a = PtAssessment::create(['reference' => 'PTA-TEST2', 'patient_id' => $this->patient()->id, 'assessed_by' => $u->id, 'pt_impression' => 'Initial']);
        $this->actingAs($u)->patch(route('assessments.update', $a), ['pt_impression' => 'Revised impression'])->assertRedirect();
        $this->assertSame('Revised impression', $a->fresh()->pt_impression);
    }

    public function test_patient_demographics_saved(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE', 'must_change_password' => false]);
        $this->actingAs($admin)->post(route('patients.store'), [
            'full_name' => 'Jane Doe', 'sex' => 'female', 'date_of_birth' => '1990-01-01',
            'phone' => '0713', 'occupation' => 'Nurse', 'religion' => 'Muslim', 'tribe' => 'Zaramo',
        ]);
        $p = Patient::where('full_name', 'Jane Doe')->firstOrFail();
        $this->assertSame('Nurse', $p->occupation);
        $this->assertSame('Zaramo', $p->tribe);
    }
}
