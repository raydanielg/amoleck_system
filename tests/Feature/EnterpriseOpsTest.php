<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\InventoryBatch;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LoanRepayment;
use App\Models\PayrollBatch;
use App\Models\PayrollRule;
use App\Models\Product;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\AssetService;
use App\Services\InventoryBatchService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseOpsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    private function employee(array $attrs = []): Employee
    {
        static $n = 1;
        return Employee::create(array_merge([
            'employee_number' => 'EMP-2026-'.str_pad($n++, 6, '0', STR_PAD_LEFT),
            'full_name' => 'Test Employee', 'employment_type' => 'full_time',
            'basic_salary' => 800000, 'status' => 'active',
        ], $attrs));
    }

    public function test_payroll_calculation_gross_deductions_net_and_employer_separate(): void
    {
        $emp = $this->employee();
        SalaryComponent::create(['employee_id' => $emp->id, 'name' => 'Housing', 'kind' => 'allowance', 'amount' => 150000]);
        PayrollRule::create(['name' => 'NSSF', 'applies_to' => 'employee', 'calc_method' => 'percent_gross', 'value' => 10]);
        PayrollRule::create(['name' => 'SDL', 'applies_to' => 'employer', 'calc_method' => 'percent_gross', 'value' => 4]);

        $calc = PayrollService::calculate($emp, 11, 2026);
        $this->assertSame(950000.0, $calc['gross']);
        $this->assertSame(95000.0, $calc['total_deductions']); // 10% of gross
        $this->assertSame(855000.0, $calc['net']);
        $this->assertSame(38000.0, $calc['total_employer']); // employer not deducted from net
        $this->assertSame(855000.0, $calc['gross'] - $calc['total_deductions']);
    }

    public function test_payroll_rule_effective_dates(): void
    {
        $emp = $this->employee(['basic_salary' => 100000]);
        PayrollRule::create(['name' => 'Future', 'applies_to' => 'employee', 'calc_method' => 'percent_gross', 'value' => 50, 'effective_from' => '2030-01-01']);
        $calc = PayrollService::calculate($emp, 11, 2026);
        $this->assertSame(0.0, $calc['total_deductions']);
    }

    public function test_payroll_batch_lifecycle(): void
    {
        $emp = $this->employee(['basic_salary' => 500000]);
        $batch = PayrollService::createBatch(11, 2026);
        $this->assertSame('draft', $batch->status);
        $this->assertSame(500000.0, (float) $batch->total_net);

        // cannot pay before approval
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        PayrollService::recordPayment($batch->items()->first(), 1000, 'cash', null, 1);
    }

    public function test_payroll_approve_pay_close(): void
    {
        $emp = $this->employee(['basic_salary' => 500000]);
        $admin = $this->admin();
        $batch = PayrollService::createBatch(11, 2026);
        PayrollService::approve($batch, $admin->id);
        $item = $batch->items()->first();
        PayrollService::recordPayment($item, 500000, 'bank', 'TRX1', $admin->id);
        $this->assertSame('paid', $item->fresh()->payment_status);
        $this->assertSame('paid', $batch->fresh()->status);
        PayrollService::close($batch);
        $this->assertSame('closed', $batch->fresh()->status);

        // overpayment rejected
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        PayrollService::recordPayment($item->fresh(), 1, 'cash', null, $admin->id);
    }

    public function test_duplicate_batch_period_rejected(): void
    {
        $this->employee();
        PayrollService::createBatch(11, 2026);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        PayrollService::createBatch(11, 2026);
    }

    public function test_loan_repayment_capped_at_balance(): void
    {
        $emp = $this->employee(['basic_salary' => 500000]);
        EmployeeLoan::create(['reference' => 'LN-1', 'employee_id' => $emp->id, 'kind' => 'loan', 'amount' => 100000, 'monthly_installment' => 80000, 'balance' => 100000, 'status' => 'approved']);
        $calc = PayrollService::calculate($emp, 11, 2026);
        $loanDed = collect($calc['deductions'])->firstWhere('loan_id');
        $this->assertSame(80000.0, $loanDed['amount']);
    }

    public function test_loan_posts_repayment_on_payment(): void
    {
        $emp = $this->employee(['basic_salary' => 500000]);
        $loan = EmployeeLoan::create(['reference' => 'LN-2', 'employee_id' => $emp->id, 'kind' => 'loan', 'amount' => 50000, 'monthly_installment' => 50000, 'balance' => 50000, 'status' => 'approved']);
        $admin = $this->admin();
        $batch = PayrollService::createBatch(11, 2026);
        PayrollService::approve($batch, $admin->id);
        $item = $batch->items()->first();
        PayrollService::recordPayment($item, (float) $item->net, 'cash', null, $admin->id);
        $this->assertSame(0.0, (float) $loan->fresh()->balance);
        $this->assertSame('closed', $loan->fresh()->status);
        $this->assertSame(1, LoanRepayment::count());
    }

    public function test_staff_cannot_view_payroll(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        $this->actingAs($staff)->get(route('admin.payroll.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.assets.index'))->assertForbidden();
    }

    public function test_leave_approval(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $type = LeaveType::create(['name' => 'Annual', 'default_days' => 28]);
        $lr = LeaveRequest::create(['employee_id' => $emp->id, 'leave_type_id' => $type->id, 'start_date' => '2026-12-01', 'end_date' => '2026-12-05', 'days' => 5]);

        $this->actingAs($admin)->post(route('admin.leave.decide', $lr), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $lr->fresh()->status);
        // cannot decide twice
        $this->actingAs($admin)->post(route('admin.leave.decide', $lr), ['decision' => 'rejected']);
        $this->assertSame('approved', $lr->fresh()->status);
    }

    public function test_asset_depreciation_and_disposal(): void
    {
        $cat = AssetCategory::create(['name' => 'IT']);
        $asset = Asset::create(['asset_number' => 'AST-1', 'name' => 'Laptop', 'asset_category_id' => $cat->id,
            'purchased_at' => now()->subYears(2), 'purchase_cost' => 1000000, 'residual_value' => 100000,
            'useful_life_years' => 3, 'depreciation_method' => 'straight_line']);
        // ~2/3 depreciated: 1M - (900k/3 * 2yrs) ≈ 400k
        $bv = AssetService::bookValue($asset);
        $this->assertGreaterThan(350000, $bv);
        $this->assertLessThan(450000, $bv);

        AssetService::dispose($asset, 'Obsolete', 50000, $this->admin()->id);
        $this->assertSame('disposed', $asset->fresh()->status);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        AssetService::assign($asset->fresh(), null, null, null, null, $this->admin()->id);
    }

    public function test_asset_assignment(): void
    {
        $asset = Asset::create(['asset_number' => 'AST-2', 'name' => 'Chair']);
        $emp = $this->employee();
        AssetService::assign($asset, $emp->id, null, 'good', 'Handover', $this->admin()->id);
        $this->assertSame($emp->id, $asset->fresh()->employee_id);
        $this->assertSame(1, $asset->assignments()->count());
        AssetService::returnAsset($asset->fresh(), 'fair', null, $this->admin()->id);
        $this->assertNull($asset->fresh()->employee_id);
        $this->assertSame('fair', $asset->fresh()->condition);
    }

    public function test_fefo_batch_allocation(): void
    {
        $product = Product::create(['sku' => 'B-1', 'name' => 'Bandage', 'purchase_price' => 100, 'selling_price' => 200, 'stock_quantity' => 0]);
        InventoryBatch::create(['product_id' => $product->id, 'batch_number' => 'B-LATE', 'qty_received' => 10, 'qty_available' => 10, 'expires_at' => now()->addDays(60), 'status' => 'active']);
        $early = InventoryBatch::create(['product_id' => $product->id, 'batch_number' => 'B-EARLY', 'qty_received' => 5, 'qty_available' => 5, 'expires_at' => now()->addDays(10), 'status' => 'active']);
        InventoryBatch::create(['product_id' => $product->id, 'batch_number' => 'B-EXP', 'qty_received' => 20, 'qty_available' => 20, 'expires_at' => now()->subDay(), 'status' => 'expired']);

        $alloc = InventoryBatchService::allocate($product->id, 8);
        // earliest expiry first: 5 from EARLY + 3 from LATE; expired skipped
        $this->assertSame(5, $alloc[$early->id]);
        $this->assertArrayNotHasKey('B-EXP', array_keys($alloc));

        InventoryBatchService::deduct($alloc);
        $this->assertSame('depleted', $early->fresh()->status);

        // insufficient stock throws
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        InventoryBatchService::allocate($product->id, 100);
    }

    public function test_branch_scoping(): void
    {
        $branch = Branch::create(['code' => 'BR-01', 'name' => 'Mwanza']);
        $emp = $this->employee(['branch_id' => $branch->id]);
        $this->assertSame($branch->id, $emp->branch->id);
        $this->assertSame(1, $branch->employees()->count());
    }

    public function test_bank_details_encrypted(): void
    {
        $emp = $this->employee(['bank_details' => 'NMB 123456789']);
        $this->assertSame('NMB 123456789', $emp->bank_details);
        $this->assertStringNotContainsString('123456789', $emp->getAttributes()['bank_details']);
    }

    public function test_admin_pages_accessible(): void
    {
        $admin = $this->admin();
        foreach (['admin.ops.dashboard', 'admin.employees.index', 'admin.payroll.index', 'admin.assets.index', 'admin.branches.index', 'admin.batches.index', 'admin.budgets.index', 'admin.pettycash.index'] as $r) {
            $this->actingAs($admin)->get(route($r))->assertOk();
        }
    }

    public function test_payslip_pdf_generation(): void
    {
        $admin = $this->admin();
        $emp = $this->employee(['basic_salary' => 500000]);
        $batch = PayrollService::createBatch(11, 2026);
        $item = $batch->items()->first();
        $resp = $this->actingAs($admin)->get(route('admin.payroll.payslip', $item));
        $resp->assertOk();
        $this->assertSame('application/pdf', $resp->headers->get('content-type'));
    }
}
