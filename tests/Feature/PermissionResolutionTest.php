<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => 'active']);
    }

    public function test_admin_has_everything(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->assertTrue($admin->hasPermission('accounting.periods'));
    }

    public function test_granting_sub_permission_implies_group_permission(): void
    {
        $user = $this->staff();
        $this->assertFalse($user->hasPermission('accounting.dashboard'));

        $user->permissionOverrides()->create(['permission' => 'accounting.reports', 'allowed' => true]);
        $user->unsetRelation('permissionOverrides');

        $this->assertTrue($user->hasPermission('accounting.reports'));
        $this->assertTrue($user->hasPermission('accounting.dashboard')); // implied — route group opens
    }

    public function test_explicit_deny_beats_implied_grant(): void
    {
        $user = $this->staff();
        $user->permissionOverrides()->createMany([
            ['permission' => 'accounting.reports', 'allowed' => true],
            ['permission' => 'accounting.dashboard', 'allowed' => false],
        ]);
        $user->unsetRelation('permissionOverrides');

        $this->assertTrue($user->hasPermission('accounting.reports'));
        $this->assertFalse($user->hasPermission('accounting.dashboard')); // explicit deny wins
    }

    public function test_revoked_permission_stops_working(): void
    {
        $user = $this->staff();
        $user->permissionOverrides()->create(['permission' => 'exec.dashboard', 'allowed' => true]);
        $user->unsetRelation('permissionOverrides');
        $this->assertTrue($user->hasPermission('exec.dashboard'));

        $user->permissionOverrides()->where('permission', 'exec.dashboard')->update(['allowed' => false]);
        $user->unsetRelation('permissionOverrides');
        $this->assertFalse($user->hasPermission('exec.dashboard'));
    }

    public function test_permission_source_explains_decision(): void
    {
        $user = $this->staff();
        $this->assertSame('role default', $user->permissionSource('patients.view')['via']);
        $this->assertSame('not granted', $user->permissionSource('exec.dashboard')['via']);

        $user->permissionOverrides()->create(['permission' => 'exec.insights', 'allowed' => true]);
        $user->unsetRelation('permissionOverrides');
        $this->assertSame('implied by exec.insights', $user->permissionSource('exec.dashboard')['via']);
    }
}
