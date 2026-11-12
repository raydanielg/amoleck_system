<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\ExpenseBudget;
use App\Models\InventoryBatch;
use App\Models\LeaveRequest;
use App\Models\PayrollBatch;
use App\Models\PettyCashTransaction;
use App\Services\InventoryBatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpsController extends Controller
{
    public function dashboard()
    {
        $batches = PayrollBatch::latest()->limit(3)->get();
        return view('admin.ops.dashboard', [
            'hr' => [
                'employees' => Employee::where('status', 'active')->count(),
                'on_leave' => LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now())->count(),
                'pending_leave' => LeaveRequest::where('status', 'pending')->count(),
                'contracts_expiring' => Employee::where('status', 'active')->whereNotNull('contract_end')->where('contract_end', '<=', now()->addDays(60))->count(),
            ],
            'payroll' => [
                'last_batch' => $batches->first(),
                'pending_paid' => PayrollBatch::where('status', 'approved')->count(),
            ],
            'assets' => [
                'total' => Asset::where('status', '!=', 'disposed')->count(),
                'value' => Asset::where('status', '!=', 'disposed')->sum('purchase_cost'),
                'warranty' => Asset::whereNotNull('warranty_expires')->where('warranty_expires', '<=', now()->addDays(60))->where('status', '!=', 'disposed')->count(),
            ],
            'inventory' => InventoryBatchService::expirySummary(),
        ]);
    }

    public function batches()
    {
        return view('admin.ops.batches', [
            'batches' => InventoryBatch::with('product', 'supplier')->latest()->paginate(25),
            'products' => \App\Models\Product::orderBy('name')->get(),
            'suppliers' => \App\Models\Supplier::orderBy('name')->get(),
            'summary' => InventoryBatchService::expirySummary(),
        ]);
    }

    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id', 'batch_number' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id', 'expires_at' => 'nullable|date',
            'manufactured_at' => 'nullable|date', 'qty' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0', 'location' => 'nullable|string|max:100',
        ]);
        $batch = InventoryBatch::create([
            'product_id' => $data['product_id'],
            'batch_number' => $data['batch_number'] ?: InventoryBatchService::nextBatchNumber($data['product_id']),
            'supplier_id' => $data['supplier_id'], 'expires_at' => $data['expires_at'],
            'manufactured_at' => $data['manufactured_at'], 'qty_received' => $data['qty'],
            'qty_available' => $data['qty'], 'unit_cost' => $data['unit_cost'],
            'location' => $data['location'], 'received_at' => now(),
        ]);
        // sync product stock
        $batch->product->increment('stock_quantity', $data['qty']);
        return back()->with('success', "Batch {$batch->batch_number} received.");
    }

    public function budgets(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $budgets = ExpenseBudget::where('month', $month)->where('year', $year)->get();
        $actual = DB::table('expenses')->where('status', 'approved')->whereMonth('expense_date', $month)->whereYear('expense_date', $year)
            ->select('category_name_snapshot', DB::raw('SUM(amount) as total'))->groupBy('category_name_snapshot')->pluck('total', 'category_name_snapshot');
        return view('admin.ops.budgets', [
            'budgets' => $budgets, 'actual' => $actual, 'month' => $month, 'year' => $year,
            'categories' => \App\Models\ExpenseCategory::orderBy('name')->pluck('name'),
            'branches' => Branch::where('status', 'active')->get(),
        ]);
    }

    public function storeBudget(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12', 'year' => 'required|integer|min:2020',
            'category' => 'required|string|max:100', 'amount' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        ExpenseBudget::updateOrCreate(
            ['month' => $data['month'], 'year' => $data['year'], 'category' => $data['category'], 'branch_id' => $data['branch_id']],
            ['amount' => $data['amount']]
        );
        return back()->with('success', 'Budget saved.');
    }

    public function pettyCash()
    {
        $txns = PettyCashTransaction::latest()->paginate(30);
        $balance = PettyCashTransaction::whereIn('type', ['allocation', 'replenishment'])->sum('amount')
            - PettyCashTransaction::where('type', 'expense')->sum('amount');
        return view('admin.ops.petty-cash', ['txns' => $txns, 'balance' => $balance]);
    }

    public function storePettyCash(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:allocation,expense,replenishment', 'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255', 'date' => 'required|date',
        ]);
        PettyCashTransaction::create($data + ['recorded_by' => auth()->id()]);
        return back()->with('success', 'Petty cash transaction recorded.');
    }
}
