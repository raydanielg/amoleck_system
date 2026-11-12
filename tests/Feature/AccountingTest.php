<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private function accountant(): User
    {
        $u = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        foreach (['accounting.dashboard', 'accounting.journals', 'accounting.reports', 'accounting.periods'] as $p) {
            $u->permissionOverrides()->create(['permission' => $p, 'allowed' => true]);
        }
        return $u->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        AccountingService::seedCoA();
    }

    public function test_unbalanced_journal_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        AccountingService::post('J1', 'Test', [
            ['account' => AccountingService::CASH, 'debit' => 100],
            ['account' => AccountingService::AR, 'credit' => 50],
        ]);
    }

    public function test_balanced_journal_posts_and_is_idempotent(): void
    {
        $e1 = AccountingService::post('J2', 'Test', [
            ['account' => AccountingService::CASH, 'debit' => 100],
            ['account' => AccountingService::SERVICE_REVENUE, 'credit' => 100],
        ], 'test', 1);
        $e2 = AccountingService::post('J2', 'Test', [
            ['account' => AccountingService::CASH, 'debit' => 100],
            ['account' => AccountingService::SERVICE_REVENUE, 'credit' => 100],
        ], 'test', 1);
        $this->assertSame($e1->id, $e2->id);
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_trial_balance_totals_equal(): void
    {
        AccountingService::post('J3', 'Sale', [
            ['account' => AccountingService::CASH, 'debit' => 200],
            ['account' => AccountingService::PRODUCT_REVENUE, 'credit' => 200],
        ]);
        $trial = AccountingService::trialBalance();
        $this->assertSame(array_sum(array_column($trial, 'debit')), array_sum(array_column($trial, 'credit')));
    }

    public function test_profit_and_loss(): void
    {
        AccountingService::post('J4', 'Rev', [
            ['account' => AccountingService::CASH, 'debit' => 1000],
            ['account' => AccountingService::SERVICE_REVENUE, 'credit' => 1000],
        ]);
        AccountingService::post('J5', 'Exp', [
            ['account' => AccountingService::EXPENSE, 'debit' => 300],
            ['account' => AccountingService::CASH, 'credit' => 300],
        ]);
        $pnl = AccountingService::profitAndLoss(now()->subDay()->toDateString(), now()->addDay()->toDateString());
        $this->assertSame(1000.0, $pnl['revenue']);
        $this->assertSame(300.0, $pnl['expenses']);
        $this->assertSame(700.0, $pnl['net']);
    }

    public function test_closed_period_blocks_posting(): void
    {
        AccountingPeriod::create(['month' => now()->month, 'year' => now()->year, 'status' => 'closed']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        AccountingService::post('J6', 'Test', [
            ['account' => AccountingService::CASH, 'debit' => 1],
            ['account' => AccountingService::AR, 'credit' => 1],
        ]);
    }

    public function test_accountant_access_and_restrictions(): void
    {
        $acc = $this->accountant();
        $this->actingAs($acc)->get(route('accounting.dashboard'))->assertOk();
        $this->actingAs($acc)->get(route('accounting.reports'))->assertOk();
        // not admin
        $this->actingAs($acc)->get(route('admin.payroll.index'))->assertForbidden();
        $this->actingAs($acc)->get(route('admin.access.index'))->assertForbidden();
    }

    public function test_staff_without_accounting_perms_blocked(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        $this->actingAs($staff)->get(route('accounting.dashboard'))->assertForbidden();
        $this->assertFalse($staff->hasPermission('accounting.dashboard'));
    }

    public function test_journal_post_permission_enforced(): void
    {
        $acc = $this->accountant(); // has journals+reports but not 'accounting.periods'? we granted it
        $acc->permissionOverrides()->updateOrCreate(['permission' => 'accounting.journals'], ['allowed' => false]);
        $this->actingAs($acc->fresh())->post(route('accounting.journals.store'), [
            'date' => now()->toDateString(), 'description' => 'x',
            'lines' => [['account_id' => 1, 'debit' => 1], ['account_id' => 2, 'credit' => 1]],
        ])->assertForbidden();
    }

    public function test_payment_verification_posts_to_ledger(): void
    {
        // full flow: invoice creates receivable, verified payment clears it
        AccountingService::post('INV-1', 'Invoice issued', [
            ['account' => AccountingService::AR, 'debit' => 5000],
            ['account' => AccountingService::SERVICE_REVENUE, 'credit' => 5000],
        ], 'invoice', 98);
        $this->assertSame(5000.0, AccountingService::balance(AccountingService::AR));
        AccountingService::post('PMT-1', 'Verified payment', [
            ['account' => AccountingService::CASH, 'debit' => 5000],
            ['account' => AccountingService::AR, 'credit' => 5000],
        ], 'payment', 99);
        $this->assertSame(5000.0, AccountingService::balance(AccountingService::CASH));
        $this->assertSame(0.0, AccountingService::balance(AccountingService::AR));
    }

    public function test_close_period_endpoint(): void
    {
        $acc = $this->accountant();
        $this->actingAs($acc)->post(route('accounting.periods.close'), ['month' => 1, 'year' => 2025])->assertRedirect();
        $this->assertSame('closed', AccountingPeriod::where('month', 1)->where('year', 2025)->first()->status);
        // posting into that period rejected
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        AccountingService::post('J7', 'Test', [
            ['account' => AccountingService::CASH, 'debit' => 1],
            ['account' => AccountingService::AR, 'credit' => 1],
        ], null, null, '2025-01-15');
    }
}
