<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServicePriceHistory;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControlCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $admin2;
    private User $staff;
    private Patient $patient;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@amoleck.co.tz')->first();
        $this->admin->update(['must_change_password' => false]);

        $this->admin2 = User::create([
            'name' => 'Admin Two', 'email' => 'admin2@test.com', 'password' => 'Password123',
            'role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->staff = User::create([
            'name' => 'Staff One', 'email' => 'staff@test.com', 'password' => 'Password123',
            'role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_number' => 'PT-TEST01', 'full_name' => 'Test Patient', 'sex' => 'male',
            'date_of_birth' => '1990-01-01', 'status' => Patient::STATUS_ACTIVE,
            'created_by' => $this->staff->id,
        ]);

        $this->service = Service::first();
    }

    private function makeInvoice(float $total = 50000): Invoice
    {
        return BillingService::createInvoice(
            $this->patient,
            [[
                'service_id' => $this->service->id,
                'name' => $this->service->name,
                'code' => $this->service->service_code,
                'unit_price' => (string) $total,
                'quantity' => 1,
            ]],
            null, today()->toDateString(), '0', null, null, $this->admin->id,
        );
    }

    public function test_admin_can_create_invoice_and_items_snapshot(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
            'patient_id' => $this->patient->id,
            'issue_date' => today()->toDateString(),
            'services' => [$this->service->id],
            'quantities' => [$this->service->id => 2],
        ]);

        $invoice = Invoice::first();
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertEquals(bcmul((string) $this->service->standard_price, '2', 2), (string) $invoice->total);
        $this->assertEquals($invoice->total, $invoice->balance);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals($this->service->name, $invoice->items->first()->service_name);
    }

    public function test_partial_payment_updates_balance(): void
    {
        $invoice = $this->makeInvoice(50000);

        BillingService::recordPayment($invoice, '20000', 'Cash', today()->toDateString(), null, $this->admin->id);

        $invoice->refresh();
        $this->assertEquals('20000.00', (string) $invoice->amount_paid);
        $this->assertEquals('30000.00', (string) $invoice->balance);
        $this->assertEquals(Invoice::STATUS_PARTIALLY_PAID, $invoice->status);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        $invoice = $this->makeInvoice(50000);
        BillingService::recordPayment($invoice, '50000', 'Cash', today()->toDateString(), null, $this->admin->id);

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertEquals('0.00', (string) $invoice->balance);
    }

    public function test_overpayment_is_rejected(): void
    {
        $invoice = $this->makeInvoice(50000);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        BillingService::recordPayment($invoice, '60000', 'Cash', today()->toDateString(), null, $this->admin->id);
    }

    public function test_refund_cannot_exceed_paid_amount(): void
    {
        $invoice = $this->makeInvoice(50000);
        $payment = BillingService::recordPayment($invoice, '30000', 'Cash', today()->toDateString(), null, $this->admin->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        BillingService::refundPayment($payment, '40000', 'Test', $this->admin->id);
    }

    public function test_refund_reopens_invoice_balance(): void
    {
        $invoice = $this->makeInvoice(50000);
        $payment = BillingService::recordPayment($invoice, '50000', 'Cash', today()->toDateString(), null, $this->admin->id);
        BillingService::refundPayment($payment, '10000', 'Overcharge', $this->admin->id);

        $invoice->refresh();
        $this->assertEquals('40000.00', (string) $invoice->amount_paid);
        $this->assertEquals('10000.00', (string) $invoice->balance);
        $this->assertEquals(Invoice::STATUS_PARTIALLY_PAID, $invoice->status);
    }

    public function test_discount_requires_reason_and_cannot_exceed_subtotal(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        BillingService::createInvoice(
            $this->patient,
            [['service_id' => null, 'name' => 'X', 'code' => null, 'unit_price' => '100', 'quantity' => 1]],
            null, today()->toDateString(), '200', null, null, $this->admin->id,
        );
    }

    public function test_invoice_with_payments_cannot_be_voided(): void
    {
        $invoice = $this->makeInvoice(50000);
        BillingService::recordPayment($invoice, '10000', 'Cash', today()->toDateString(), null, $this->admin->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        BillingService::voidInvoice($invoice, 'test', $this->admin->id);
    }

    public function test_staff_cannot_access_financial_routes(): void
    {
        foreach (['/admin/financials', '/admin/invoices', '/admin/payments', '/admin/refunds', '/admin/expense-approvals', '/admin/staff'] as $url) {
            $this->actingAs($this->staff)->get($url)->assertForbidden();
        }
    }

    public function test_expense_approval_workflow_logs_decisions(): void
    {
        $this->seed(); // ensure settings exist
        \App\Support\Settings::set('expense_approval_mode', '1');
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\ClinicSetting::where('setting_key', 'expense_approval_mode')->update(['setting_value' => '1']);

        $category = ExpenseCategory::first();
        $expense = Expense::create([
            'expense_reference' => 'EXP-T001',
            'title' => 'Supplies',
            'expense_category_id' => $category->id,
            'amount' => '25000',
            'expense_date' => today(),
            'status' => Expense::STATUS_SUBMITTED,
            'recorded_by' => $this->staff->id,
        ]);

        // Admin approves
        $response = $this->actingAs($this->admin)->post(route('admin.expense-approvals.decide', $expense), [
            'decision' => 'approve',
        ]);
        $response->assertRedirect();

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->status);

        $log = ExpenseApproval::first();
        $this->assertEquals('approved', $log->decision);
        $this->assertEquals('submitted', $log->previous_status);
        $this->assertEquals($this->admin->id, $log->reviewer_id);
    }

    public function test_submitter_cannot_approve_own_expense(): void
    {
        $expense = Expense::create([
            'expense_reference' => 'EXP-T002',
            'title' => 'Admin own expense',
            'expense_category_id' => ExpenseCategory::first()->id,
            'amount' => '10000',
            'expense_date' => today(),
            'status' => Expense::STATUS_SUBMITTED,
            'recorded_by' => $this->admin->id,
        ]);

        // The submitting admin cannot approve — but a different admin can
        $response = $this->actingAs($this->admin)->post(route('admin.expense-approvals.decide', $expense), [
            'decision' => 'approve',
        ]);
        $response->assertSessionHasErrors('decision');
        $this->assertEquals(Expense::STATUS_SUBMITTED, $expense->fresh()->status);

        $this->actingAs($this->admin2)->post(route('admin.expense-approvals.decide', $expense), [
            'decision' => 'approve',
        ]);
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->fresh()->status);
    }

    public function test_returned_expense_can_be_resubmitted(): void
    {
        $category = ExpenseCategory::first();
        $expense = Expense::create([
            'expense_reference' => 'EXP-T003',
            'title' => 'Fix me',
            'expense_category_id' => $category->id,
            'amount' => '5000',
            'expense_date' => today(),
            'status' => Expense::STATUS_SUBMITTED,
            'recorded_by' => $this->staff->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.expense-approvals.decide', $expense), [
            'decision' => 'return', 'reason' => 'Wrong category',
        ]);
        $this->assertEquals(Expense::STATUS_RETURNED, $expense->fresh()->status);

        // Staff edits -> resubmits
        $this->actingAs($this->staff)->put(route('expenses.update', $expense), [
            'title' => 'Fixed title',
            'expense_category_id' => $category->id,
            'amount' => '5000',
            'expense_date' => today()->toDateString(),
        ]);

        $this->assertEquals(Expense::STATUS_SUBMITTED, $expense->fresh()->status);
        $this->assertTrue(ExpenseApproval::where('decision', 'resubmitted')->exists());
    }

    public function test_notifications_created_on_invoice_and_payment(): void
    {
        $invoice = $this->makeInvoice(10000);
        $this->assertTrue(Notification::where('type', 'invoice.created')->exists());

        BillingService::recordPayment($invoice, '10000', 'Cash', today()->toDateString(), null, $this->admin->id);
        $this->assertTrue(Notification::where('type', 'payment.recorded')->exists());
    }

    public function test_service_price_change_records_history_but_keeps_invoice_snapshot(): void
    {
        $oldPrice = (string) $this->service->standard_price;
        $invoice = $this->makeInvoice(0);
        // invoice item carries the old price snapshot
        $itemPrice = $invoice->items->first()->unit_price;

        $this->actingAs($this->admin)->put(route('admin.services.update', $this->service), [
            'name' => $this->service->name,
            'service_category_id' => $this->service->service_category_id,
            'service_code' => $this->service->service_code,
            'standard_price' => ((float) $oldPrice) + 5000,
            'duration_minutes' => $this->service->duration_minutes,
            'requires_notes' => $this->service->requires_notes,
            'sort_order' => $this->service->sort_order,
        ]);

        $history = ServicePriceHistory::first();
        $this->assertNotNull($history);
        $this->assertEquals($oldPrice ?: '0.00', (string) $history->old_price);

        // Historical invoice unchanged
        $this->assertEquals($itemPrice, $invoice->fresh()->items->first()->unit_price);
    }

    public function test_financial_reports_are_admin_only(): void
    {
        $this->actingAs($this->staff)->get('/reports/revenue')->assertForbidden();
        $this->actingAs($this->admin)->get('/reports/revenue')->assertOk();
        $this->actingAs($this->staff)->get('/reports/staff-activity')->assertForbidden();
    }

    public function test_staff_can_use_appointments_and_followups(): void
    {
        $this->actingAs($this->staff)->get('/appointments')->assertOk();
        $this->actingAs($this->staff)->get('/follow-ups')->assertOk();
        $this->actingAs($this->staff)->get('/notifications')->assertOk();
    }

    public function test_appointment_status_flow(): void
    {
        $this->actingAs($this->staff)->post(route('appointments.store'), [
            'patient_id' => $this->patient->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);

        $appointment = \App\Models\Appointment::first();
        $this->assertNotNull($appointment);

        $this->actingAs($this->staff)->patch(route('appointments.status', $appointment), [
            'status' => 'confirmed',
        ]);
        $this->assertEquals('confirmed', $appointment->fresh()->status);
    }

    public function test_visit_followup_date_creates_followup(): void
    {
        $this->actingAs($this->staff)->post(route('patients.visits.store', $this->patient), [
            'visit_datetime' => now()->format('Y-m-d\TH:i'),
            'chief_complaint' => 'Back pain',
            'follow_up_date' => now()->addWeek()->toDateString(),
            'services' => [$this->service->id],
        ]);

        $this->assertTrue(\App\Models\FollowUp::where('patient_id', $this->patient->id)->exists());
    }
}
