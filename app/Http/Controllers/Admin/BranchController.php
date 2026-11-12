<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.branches.index', [
            'branches' => Branch::withCount('employees')->latest()->get(),
            'consolidated' => [
                'revenue' => (float) DB::table('invoices')->sum('total') + (float) DB::table('sales')->sum('total'),
                'expenses' => (float) DB::table('expenses')->where('status', 'approved')->sum('amount'),
                'patients' => DB::table('patients')->count(),
                'employees' => DB::table('employees')->where('status', 'active')->count(),
                'assets' => DB::table('assets')->where('status', '!=', 'disposed')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'type' => 'required|in:clinic,store,office,warehouse,other',
            'address' => 'nullable|string|max:255', 'region' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150', 'manager' => 'nullable|string|max:150',
            'opened_at' => 'nullable|date',
        ]);
        $data['code'] = sprintf('BR-%03d', Branch::count() + 1);
        $branch = Branch::create($data);
        AuditLogger::log('branch.created', 'branch', $branch->id);
        return back()->with('success', "Branch {$branch->code} created.");
    }

    public function show(Branch $branch)
    {
        return view('admin.branches.show', [
            'branch' => $branch->load('employees.department'),
            'stats' => [
                'patients' => $branch->patients()->count(),
                'revenue' => (float) $branch->invoices()->sum('total') + (float) $branch->sales()->sum('total'),
                'expenses' => (float) $branch->expenses()->sum('amount'),
                'assets' => $branch->assets()->count(),
                'employees' => $branch->employees()->where('status', 'active')->count(),
            ],
        ]);
    }
}
