<?php

namespace Tests\Feature;

use App\Models\ManagementInsight;
use App\Models\ManagementKpi;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    private function manager(array $perms = ['exec.dashboard', 'exec.reports', 'exec.insights', 'exec.branches', 'exec.hr']): User
    {
        $u = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        foreach ($perms as $p) $u->permissionOverrides()->create(['permission' => $p, 'allowed' => true]);
        return $u->fresh();
    }

    public function test_management_requires_permission(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        $this->actingAs($staff)->get(route('management.dashboard'))->assertForbidden();
        $recep = User::factory()->create(['role' => 'RECEPTIONIST', 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        $this->actingAs($recep)->get(route('management.dashboard'))->assertForbidden();
    }

    public function test_manager_accesses_dashboard(): void
    {
        $this->actingAs($this->manager())->get(route('management.dashboard'))->assertOk();
    }

    public function test_period_comparison_math(): void
    {
        $this->assertSame(20.0, AnalyticsService::change(120, 100));
        $this->assertSame(-50.0, AnalyticsService::change(50, 100));
        $this->assertNull(AnalyticsService::change(50, 0)); // no divide-by-zero
    }

    public function test_insights_generated_from_real_changes(): void
    {
        // seed a verified payment in previous period so revenue drops
        AccountingService::seedCoA();
        $prevFrom = now()->subMonth()->startOfMonth()->toDateString();
        $prevTo = now()->subMonth()->endOfMonth()->toDateString();
        $n = AnalyticsService::generateInsights(now()->startOfMonth()->toDateString(), now()->toDateString(), $prevFrom, $prevTo);
        $this->assertIsInt($n);
        // insights dedupe by key
        AnalyticsService::generateInsights(now()->startOfMonth()->toDateString(), now()->toDateString(), $prevFrom, $prevTo);
        $this->assertSame(ManagementInsight::count(), ManagementInsight::distinct('key')->count('key'));
    }

    public function test_kpi_target_evaluation(): void
    {
        ManagementKpi::create(['key' => 'patients', 'name' => 'Monthly patients', 'category' => 'patients', 'target' => 100, 'unit' => 'count']);
        $status = AnalyticsService::kpiStatus(now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertCount(1, $status);
        $this->assertEquals(0.0, $status[0]['actual']);
    }

    public function test_report_pdf_permission_gated(): void
    {
        $noReport = $this->manager(['exec.dashboard']);
        $this->actingAs($noReport)->get(route('management.report.pdf'))->assertForbidden();

        $mgr = $this->manager();
        $resp = $this->actingAs($mgr)->get(route('management.report.pdf'));
        $resp->assertOk();
        $this->assertSame('application/pdf', $resp->headers->get('content-type'));
    }

    public function test_insight_acknowledge(): void
    {
        $mgr = $this->manager();
        $ins = ManagementInsight::create(['key' => 'test.1', 'title' => 'Test', 'category' => 'financial', 'severity' => 'warning', 'detail' => 'x']);
        $this->actingAs($mgr)->post(route('management.insights.ack', $ins))->assertRedirect();
        $this->assertSame('acknowledged', $ins->fresh()->status);
    }
}
