<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $staff = User::where('role', User::ROLE_CLINIC_STAFF)
            ->withCount([
                'patients',
                'visits',
                'expenses',
            ])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.staff.index', ['staff' => $staff]);
    }

    public function show(User $user): View
    {
        abort_unless($user->role === User::ROLE_CLINIC_STAFF, 404);

        $monthStart = now()->startOfMonth();

        return view('admin.staff.show', [
            'staff' => $user,
            'patientsTotal' => Patient::where('created_by', $user->id)->count(),
            'patientsMonth' => Patient::where('created_by', $user->id)->where('created_at', '>=', $monthStart)->count(),
            'visitsTotal' => Visit::where('recorded_by', $user->id)->count(),
            'visitsMonth' => Visit::where('recorded_by', $user->id)->where('created_at', '>=', $monthStart)->count(),
            'expensesTotal' => Expense::where('recorded_by', $user->id)->count(),
            'expensesMonth' => Expense::where('recorded_by', $user->id)->where('created_at', '>=', $monthStart)->count(),
            'expenseAmountMonth' => Expense::where('recorded_by', $user->id)->whereIn('status', Expense::countableStatuses())->where('created_at', '>=', $monthStart)->sum('amount'),
            'recentPatients' => Patient::where('created_by', $user->id)->latest()->limit(8)->get(),
            'recentVisits' => Visit::with('patient')->where('recorded_by', $user->id)->latest('visit_datetime')->limit(8)->get(),
            'recentExpenses' => Expense::with('category')->where('recorded_by', $user->id)->latest('expense_date')->limit(8)->get(),
            'lastActivity' => \App\Models\AuditLog::where('user_id', $user->id)->latest()->first(),
        ]);
    }
}
