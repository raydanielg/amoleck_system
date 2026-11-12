<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $temporaryPassword = Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => User::ROLE_CLINIC_STAFF,
            'status' => User::STATUS_ACTIVE,
            'must_change_password' => true,
        ]);

        AuditLogger::log('user.created', 'user', $user->id, null, ['email' => $user->email, 'role' => $user->role]);

        return redirect()->route('admin.users.index')
            ->with('success', "Staff account created. Temporary password: {$temporaryPassword}");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]);

        $old = $user->only(['name', 'email']);
        $user->update($validated);

        AuditLogger::log('user.updated', 'user', $user->id, $old, $validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot deactivate your own account.');

        $new = $user->status === User::STATUS_ACTIVE ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
        $user->update(['status' => $new]);

        AuditLogger::log($new === User::STATUS_ACTIVE ? 'user.activated' : 'user.deactivated', 'user', $user->id);

        if ($new === User::STATUS_INACTIVE) {
            \App\Services\NotificationService::notifyAdmins(
                'user.deactivated', 'Staff account deactivated',
                "{$user->name}'s account was deactivated by {$request->user()->name}",
                route('admin.users.index'),
            );
        }

        return back()->with('success', 'User '.($new === User::STATUS_ACTIVE ? 'activated' : 'deactivated').'.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $temporaryPassword = Str::password(12);

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        AuditLogger::log('user.password_reset', 'user', $user->id);

        return back()->with('success', "Password reset. New temporary password: {$temporaryPassword}");
    }
}
