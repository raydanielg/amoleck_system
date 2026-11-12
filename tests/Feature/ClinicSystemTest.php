<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@amoleck.co.tz')->first();
        $this->admin->update(['must_change_password' => false]);

        $this->staff = User::create([
            'name' => 'Staff One',
            'email' => 'staff@test.com',
            'password' => 'Password123',
            'role' => User::ROLE_CLINIC_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_staff_cannot_access_admin_routes(): void
    {
        foreach (['/admin/users', '/admin/services', '/admin/settings', '/admin/audit-logs'] as $url) {
            $this->actingAs($this->staff)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_access_admin_routes(): void
    {
        foreach (['/admin/users', '/admin/services', '/admin/settings', '/admin/audit-logs'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_staff_can_register_patient_with_auto_number(): void
    {
        $response = $this->actingAs($this->staff)->post('/patients', [
            'full_name' => 'John Mwangi',
            'sex' => 'male',
            'date_of_birth' => '1990-05-15',
            'phone' => '0712345678',
        ]);

        $patient = Patient::first();
        $this->assertNotNull($patient);
        $this->assertStringStartsWith('PT-', $patient->patient_number);
        $response->assertRedirect(route('patients.show', $patient));
    }

    public function test_duplicate_patient_warning(): void
    {
        Patient::create([
            'patient_number' => 'PT-00001',
            'full_name' => 'Jane Doe',
            'sex' => 'female',
            'date_of_birth' => '1985-01-01',
            'phone' => '0700000000',
            'status' => 'active',
        ]);

        $this->actingAs($this->staff)->post('/patients', [
            'full_name' => 'Jane Doe',
            'sex' => 'female',
            'date_of_birth' => '1985-01-01',
        ])->assertSessionHas('warning');
    }

    public function test_visit_creation_with_service_snapshot(): void
    {
        $patient = Patient::create([
            'patient_number' => 'PT-00001', 'full_name' => 'Test P', 'sex' => 'male',
            'date_of_birth' => '1990-01-01', 'status' => 'active',
        ]);
        $service = Service::first();

        $this->actingAs($this->staff)->post(route('patients.visits.store', $patient), [
            'visit_datetime' => now()->format('Y-m-d\TH:i'),
            'chief_complaint' => 'Back pain',
            'services' => [$service->id],
        ]);

        $visit = Visit::first();
        $this->assertNotNull($visit);
        $this->assertStringStartsWith('VS-', $visit->visit_reference);
        $this->assertEquals($service->name, $visit->services->first()->service_name_snapshot);
    }

    public function test_inactive_service_not_snapshot_in_new_visit(): void
    {
        $patient = Patient::create([
            'patient_number' => 'PT-00001', 'full_name' => 'Test P', 'sex' => 'male',
            'date_of_birth' => '1990-01-01', 'status' => 'active',
        ]);
        $service = Service::first();
        $service->update(['is_active' => false]);

        $this->actingAs($this->staff)->post(route('patients.visits.store', $patient), [
            'visit_datetime' => now()->format('Y-m-d\TH:i'),
            'services' => [$service->id],
        ]);

        $this->assertEquals(0, Visit::first()->services->count());
    }

    public function test_staff_cannot_void_visit(): void
    {
        $patient = Patient::create([
            'patient_number' => 'PT-00001', 'full_name' => 'T', 'sex' => 'male',
            'date_of_birth' => '1990-01-01', 'status' => 'active',
        ]);
        $visit = Visit::create([
            'visit_reference' => 'VS-00001', 'patient_id' => $patient->id,
            'visit_datetime' => now(), 'status' => 'completed', 'recorded_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)->post(route('admin.visits.void', $visit), [
            'void_reason' => 'Mistake',
        ])->assertForbidden();
    }

    public function test_expense_validation_rejects_negative_amount(): void
    {
        $this->actingAs($this->staff)->post('/expenses', [
            'title' => 'Test', 'expense_category_id' => ExpenseCategory::first()->id,
            'amount' => -50, 'expense_date' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');
    }

    public function test_expense_created_with_reference_and_snapshot(): void
    {
        $category = ExpenseCategory::first();

        $this->actingAs($this->staff)->post('/expenses', [
            'title' => 'Supplies', 'expense_category_id' => $category->id,
            'amount' => 50000, 'expense_date' => now()->toDateString(),
            'payment_method' => 'Cash',
        ]);

        $expense = Expense::first();
        $this->assertStringStartsWith('EXP-', $expense->expense_reference);
        $this->assertEquals($category->name, $expense->category_name_snapshot);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->staff->update(['status' => User::STATUS_INACTIVE]);

        $this->post('/login', [
            'email' => 'staff@test.com',
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_audit_log_records_patient_creation(): void
    {
        $this->actingAs($this->staff)->post('/patients', [
            'full_name' => 'Audit Test', 'sex' => 'female', 'date_of_birth' => '2000-01-01',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'patient.created',
            'entity_type' => 'patient',
            'user_id' => $this->staff->id,
        ]);
    }

    public function test_reports_page_loads_and_csv_exports(): void
    {
        $this->actingAs($this->staff)->get('/reports/expenses')->assertOk();
        $response = $this->actingAs($this->staff)->get('/reports/patient-register/export/csv');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_dashboard_loads(): void
    {
        $this->actingAs($this->staff)->get('/dashboard')->assertOk();
    }
}
