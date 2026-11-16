<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => User::ROLE_SALES_MANAGER, 'status' => 'active']);
    }

    private function leadPayload(): array
    {
        return [
            'full_name' => 'Amina Juma', 'phone' => '0712345678',
            'received_at' => now()->toDateString(), 'lead_source_id' => null,
        ];
    }

    public function test_admin_registers_a_lead(): void
    {
        $response = $this->actingAs($this->admin())->post('/leads', $this->leadPayload());

        $lead = Lead::first();
        $this->assertNotNull($lead);
        $this->assertStringStartsWith('LD-', $lead->lead_reference);
        $response->assertRedirect(route('leads.show', $lead));
        $this->assertEquals('created', $lead->activities()->first()->type);
    }

    public function test_lead_assignment_notifies_manager(): void
    {
        $admin = $this->admin();
        $manager = $this->manager();
        $lead = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001', 'created_by' => $admin->id]);

        $this->actingAs($admin)->post("/leads/{$lead->id}/assign", ['assigned_to' => $manager->id])->assertRedirect();

        $lead->refresh();
        $this->assertSame($manager->id, $lead->assigned_to);
        $this->assertSame('lead.assigned', $manager->notifications()->first()->type);
    }

    public function test_sales_manager_only_sees_assigned_leads(): void
    {
        $admin = $this->admin();
        $mine = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001', 'assigned_to' => $this->manager()->id]);
        $other = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00002', 'full_name' => 'Other Person', 'phone' => '0700000000']);

        $manager = $mine->assignee;
        $this->actingAs($manager)->get("/leads/{$other->id}")->assertForbidden();
        $this->actingAs($manager)->get("/leads/{$mine->id}")->assertOk();
    }

    public function test_follow_up_is_recorded_without_overwriting_history(): void
    {
        $manager = $this->manager();
        $lead = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001', 'assigned_to' => $manager->id]);

        $this->actingAs($manager)->post("/leads/{$lead->id}/follow-ups", [
            'reached' => 1, 'interest_level' => 'interested', 'notes' => 'Promised to visit',
            'next_followup_at' => now()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $lead->refresh();
        $this->assertSame(1, $lead->followUps()->count());
        $this->assertSame('contacted', $lead->contact_status);
        $this->assertSame('interested', $lead->interest_level);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $admin = $this->admin();
        $lead = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001']);

        // expected → service_received is not a valid jump
        $this->actingAs($admin)->patch("/leads/{$lead->id}/status", ['visit_status' => 'service_received'])
            ->assertStatus(422);
        $this->assertSame('expected', $lead->refresh()->visit_status);

        // expected → confirmed IS valid
        $this->actingAs($admin)->patch("/leads/{$lead->id}/status", ['visit_status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $lead->refresh()->visit_status);
    }

    public function test_convert_links_existing_patient_without_duplicating(): void
    {
        $admin = $this->admin();
        $patient = Patient::create([
            'patient_number' => 'PT-00001', 'full_name' => 'Amina Juma',
            'phone' => '0712345678', 'status' => 'active',
        ]);
        $lead = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001']);

        $this->actingAs($admin)->post("/leads/{$lead->id}/convert", ['patient_id' => $patient->id])->assertRedirect();

        $lead->refresh();
        $this->assertSame($patient->id, $lead->converted_patient_id);
        $this->assertSame('visited', $lead->visit_status);
        $this->assertSame(1, Patient::count());
    }

    public function test_convert_can_create_new_patient(): void
    {
        $admin = $this->admin();
        $lead = Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001']);

        $this->actingAs($admin)->post("/leads/{$lead->id}/convert", ['create_patient' => 1, 'sex' => 'female'])->assertRedirect();

        $lead->refresh();
        $this->assertNotNull($lead->converted_patient_id);
        $this->assertSame('Amina Juma', $lead->patient->full_name);
        $this->assertStringStartsWith('PT-', $lead->patient->patient_number);
    }

    public function test_unauthorized_user_cannot_access_leads(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => 'active']);
        $user->permissionOverrides()->create(['permission' => 'leads.view', 'allowed' => false]);
        $user->unsetRelation('permissionOverrides');

        // CLINIC_STAFF has no leads.* by default → blocked
        $this->actingAs($user)->get('/leads')->assertForbidden();
    }

    public function test_leads_report_export_respects_permission(): void
    {
        $admin = $this->admin();
        Lead::create([...$this->leadPayload(), 'lead_reference' => 'LD-00001']);

        // Admin can view + export
        $this->actingAs($admin)->get('/reports/leads')->assertOk();
        $this->actingAs($admin)->get('/reports/leads/export/csv')->assertOk();

        // Excel export returns a real .xlsx
        $xlsx = $this->actingAs($admin)->get('/reports/leads/export/xlsx');
        $xlsx->assertOk();
        $xlsx->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', file_get_contents($xlsx->baseResponse->getFile()->getPathname()));

        // Staff without leads.reports is denied both
        $user = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => 'active']);
        $this->actingAs($user)->get('/reports/leads')->assertForbidden();
        $this->actingAs($user)->get('/reports/leads/export/csv')->assertForbidden();
    }
}
