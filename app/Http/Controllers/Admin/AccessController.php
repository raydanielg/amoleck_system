<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosition;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class AccessController extends Controller
{
    public function index()
    {
        return view('admin.access.index', [
            'users' => User::with('employee')->orderBy('name')->get(),
            'groups' => Permissions::groups(),
        ]);
    }

    /** Create staff account (user + optional linked employee record). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200', 'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_RECEPTIONIST, User::ROLE_CLINIC_STAFF])],
            'phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|exists:departments,id',
            'job_position_id' => 'nullable|exists:job_positions,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']), 'role' => $data['role'],
                'status' => User::STATUS_ACTIVE, 'must_change_password' => true,
            ]);
            if (! empty($data['department_id']) || ! empty($data['job_position_id'])) {
                $emp = Employee::create([
                    'employee_number' => sprintf('EMP-%d-%06d', now()->year, Employee::whereYear('created_at', now()->year)->count() + 1),
                    'user_id' => $user->id, 'full_name' => $data['name'], 'email' => $data['email'],
                    'phone' => $data['phone'] ?? null, 'department_id' => $data['department_id'],
                    'job_position_id' => $data['job_position_id'], 'branch_id' => $data['branch_id'] ?? null,
                    'hired_at' => now(),
                ]);
            }
        });
        AuditLogger::log('staff.created', 'user', null);
        return back()->with('success', 'Staff account created. They must change the password at first login.');
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_RECEPTIONIST, User::ROLE_CLINIC_STAFF])]]);
        abort_if($user->id === auth()->id() && $data['role'] !== User::ROLE_ADMIN, 422, 'You cannot remove your own admin role.');
        $user->update(['role' => $data['role']]);
        AuditLogger::log('staff.role_changed', 'user', $user->id, ['role' => null], ['role' => $data['role']]);
        return back()->with('success', 'Role updated.');
    }

    /** Toggle a single permission for a user (override of role default). */
    public function setPermission(Request $request, User $user)
    {
        $data = $request->validate([
            'permission' => ['required', Rule::in(array_keys(Permissions::CATALOG))],
            'allowed' => 'required|boolean',
        ]);
        $user->permissionOverrides()->updateOrCreate(
            ['permission' => $data['permission']],
            ['allowed' => $data['allowed']]
        );
        AuditLogger::log('staff.permission', 'user', $user->id, null, [$data['permission'] => (bool) $data['allowed']]);
        return back()->with('success', 'Permission updated.');
    }
}
