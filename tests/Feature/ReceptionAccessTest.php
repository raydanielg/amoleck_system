<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\HrRequest;
use App\Models\LeaveType;
use App\Models\Patient;
use App\Models\PatientQueue;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    private function admin(): User
    {
        return $this->user(User::ROLE_ADMIN);
    }

    private function receptionist(): User
    {
        return $this->user(User::ROLE_RECEPTIONIST);
    }

    public function test_receptionist_role_defaults(): void
    {
        $r = $this->receptionist();
        $this->assertTrue($r->hasPermission('patients.register'));
        $this->assertTrue($r->hasPermission('reception.queue'));
        $this->assertFalse($r->hasPermission('clinical.view'));
        $this->assertFalse($r->hasPermission('self.payslip')); // off by default — must be granted
    }

    public function test_admin_can_grant_and_revoke_individual_permissions(): void
    {
        $r = $this->receptionist();
        $r->permissionOverrides()->create(['permission' => 'self.payslip', 'allowed' => true]);
        $this->assertTrue($r->fresh()->hasPermission('self.payslip'));

        $r->permissionOverrides()->updateOrCreate(['permission' => 'payments.record'], ['allowed' => false]);
        $this->assertFalse($r->fresh()->hasPermission('payments.record'));
    }

    public function test_admin_creates_receptionist_account(): void
    {
        $this->actingAs($this->admin())->post(route('admin.access.users.store'), [
            'name' => 'Reception A', 'email' => 'ra@test.com', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role' => 'RECEPTIONIST',
        ])->assertRedirect();
        $this->assertSame('RECEPTIONIST', User::where('email', 'ra@test.com')->first()->role);
    }

    public function test_receptionist_cannot_access_admin_or_clinical(): void
    {
        $r = $this->receptionist();
        $this->actingAs($r)->get(route('admin.payroll.index'))->assertForbidden();
        $this->actingAs($r)->get(route('admin.access.index'))->assertForbidden();
        $patient = Patient::create(['patient_number' => 'P-1', 'full_name' => 'T', 'gender' => 'female', 'phone' => '0712']);
        $this->actingAs($r)->get(route('patients.visits.create', $patient))->assertForbidden(); // clinical
    }

    public function test_receptionist_can_register_patient_and_checkin(): void
    {
        $r = $this->receptionist();
        $patient = Patient::create(['patient_number' => 'P-2', 'full_name' => 'T', 'gender' => 'male', 'phone' => '0713']);
        $this->actingAs($r)->post(route('reception.checkin'), ['patient_id' => $patient->id])->assertRedirect();
        $entry = PatientQueue::first();
        $this->assertTrue($entry->is_walkin);
        $this->assertSame('waiting', $entry->status);
        $this->assertStringStartsWith('Q-', $entry->queue_number);
    }

    public function test_queue_workflow_and_duplicate_checkin_blocked(): void
    {
        $r = $this->receptionist();
        $patient = Patient::create(['patient_number' => 'P-3', 'full_name' => 'T', 'gender' => 'female', 'phone' => '0714']);
        $this->actingAs($r)->post(route('reception.checkin'), ['patient_id' => $patient->id]);
        // duplicate blocked
        $this->actingAs($r)->post(route('reception.checkin'), ['patient_id' => $patient->id]);
        $this->assertSame(1, PatientQueue::count());

        $q = PatientQueue::first();
        $this->actingAs($r)->post(route('reception.queue.update', $q), ['action' => 'call']);
        $this->actingAs($r)->post(route('reception.queue.update', $q), ['action' => 'start']);
        $this->actingAs($r)->post(route('reception.queue.update', $q), ['action' => 'complete']);
        $this->assertSame('completed', $q->fresh()->status);
        // invalid transition rejected
        $this->actingAs($r)->post(route('reception.queue.update', $q), ['action' => 'call']);
        $this->assertSame('completed', $q->fresh()->status);
    }

    public function test_staff_clock_in_once_and_clock_out(): void
    {
        $staff = $this->user(User::ROLE_CLINIC_STAFF);
        $emp = Employee::create(['employee_number' => 'EMP-X', 'user_id' => $staff->id, 'full_name' => 'S', 'employment_type' => 'full_time', 'basic_salary' => 100]);

        $this->actingAs($staff)->post(route('my.clockin'))->assertRedirect();
        $rec = AttendanceRecord::first();
        $this->assertNotNull($rec->check_in);

        // duplicate clock-in blocked
        $this->actingAs($staff)->post(route('my.clockin'));
        $this->assertSame(1, AttendanceRecord::count());

        $this->actingAs($staff)->post(route('my.clockout'));
        $this->assertNotNull($rec->fresh()->check_out);
    }

    public function test_employee_sees_only_own_payslip(): void
    {
        $admin = $this->admin();
        $me = $this->user(User::ROLE_CLINIC_STAFF);
        $myEmp = Employee::create(['employee_number' => 'EMP-1', 'user_id' => $me->id, 'full_name' => 'Me', 'employment_type' => 'full_time', 'basic_salary' => 100]);
        $other = Employee::create(['employee_number' => 'EMP-2', 'full_name' => 'Other', 'employment_type' => 'full_time', 'basic_salary' => 200]);

        $batch = PayrollService::createBatch(11, 2026);
        PayrollService::approve($batch, $admin->id);
        $myItem = PayrollItem::where('employee_id', $myEmp->id)->first();
        $otherItem = PayrollItem::where('employee_id', $other->id)->first();

        // unpaid batch: payslip not released yet
        $this->actingAs($me)->get(route('my.payslip', $myItem))->assertForbidden();

        PayrollService::recordPayment($myItem, (float) $myItem->net, 'cash', null, $admin->id);
        PayrollService::recordPayment($otherItem, (float) $otherItem->net, 'cash', null, $admin->id);

        // own payslip OK
        $this->actingAs($me)->get(route('my.payslip', $myItem->fresh()))->assertOk();
        // another employee's payslip blocked (IDOR)
        $this->actingAs($me)->get(route('my.payslip', $otherItem->fresh()))->assertForbidden();
    }

    public function test_hr_request_submission(): void
    {
        $staff = $this->user(User::ROLE_CLINIC_STAFF);
        $emp = Employee::create(['employee_number' => 'EMP-3', 'user_id' => $staff->id, 'full_name' => 'S', 'employment_type' => 'full_time', 'basic_salary' => 100]);
        $this->actingAs($staff)->post(route('my.requests'), [
            'type' => 'attendance_correction', 'subject' => 'Forgot to clock out', 'details' => 'Left at 17:00',
        ])->assertRedirect();
        $this->assertSame('submitted', HrRequest::first()->status);
    }

    public function test_permission_toggle_via_admin_endpoint(): void
    {
        $r = $this->receptionist();
        $this->actingAs($this->admin())->post(route('admin.access.users.permission', $r), [
            'permission' => 'self.payslip', 'allowed' => 1,
        ])->assertRedirect();
        $this->assertTrue($r->fresh()->hasPermission('self.payslip'));

        // invalid permission key rejected
        $this->actingAs($this->admin())->post(route('admin.access.users.permission', $r), [
            'permission' => 'fake.perm', 'allowed' => 1,
        ])->assertSessionHasErrors();
    }
}
