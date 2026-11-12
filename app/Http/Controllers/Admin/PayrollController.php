<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollRule;
use App\Services\PayrollService;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index()
    {
        return view('admin.payroll.index', [
            'batches' => PayrollBatch::latest('year')->latest('month')->paginate(15),
            'rules' => PayrollRule::orderBy('name')->get(),
            'loans' => EmployeeLoan::with('employee')->where('status', '!=', 'closed')->latest()->get(),
            'employees' => Employee::where('status', 'active')->orderBy('full_name')->get(),
        ]);
    }

    public function createBatch(Request $request)
    {
        $data = $request->validate(['month' => 'required|integer|min:1|max:12', 'year' => 'required|integer|min:2020|max:2100']);
        $batch = PayrollService::createBatch($data['month'], $data['year']);
        return redirect()->route('admin.payroll.show', $batch)->with('success', "Payroll {$batch->reference} created.");
    }

    public function show(PayrollBatch $payrollBatch)
    {
        $payrollBatch->load('items.employee.department', 'items.payments');
        return view('admin.payroll.show', ['batch' => $payrollBatch]);
    }

    public function approve(PayrollBatch $payrollBatch)
    {
        PayrollService::approve($payrollBatch, auth()->id());
        return back()->with('success', 'Payroll approved — payments can now be recorded.');
    }

    public function pay(Request $request, PayrollItem $payrollItem)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01', 'method' => 'required|in:bank,cash,mobile',
            'reference' => 'nullable|string|max:100',
        ]);
        PayrollService::recordPayment($payrollItem, (float) $data['amount'], $data['method'], $data['reference'], auth()->id());
        return back()->with('success', 'Salary payment recorded.');
    }

    public function close(PayrollBatch $payrollBatch)
    {
        PayrollService::close($payrollBatch);
        return back()->with('success', 'Payroll closed and locked.');
    }

    public function payslip(PayrollItem $payrollItem)
    {
        $payrollItem->load('employee.department', 'employee.jobPosition', 'batch');
        $pdf = Pdf::loadView('pdf.payslip', ['item' => $payrollItem, 'clinic' => ['clinic_name' => \App\Support\Settings::get('clinic_name')]]);
        return $pdf->download("payslip-{$payrollItem->employee->employee_number}-{$payrollItem->batch->reference}.pdf");
    }

    public function storeRule(Request $request)
    {
        PayrollRule::create($request->validate([
            'name' => 'required|string|max:100', 'applies_to' => 'required|in:employee,employer',
            'calc_method' => 'required|in:fixed,percent_gross,percent_basic',
            'value' => 'required|numeric|min:0', 'effective_from' => 'nullable|date', 'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]));
        return back()->with('success', 'Payroll rule saved. Verify statutory rates with your accountant before production use.');
    }

    public function toggleRule(PayrollRule $payrollRule)
    {
        $payrollRule->update(['is_active' => ! $payrollRule->is_active]);
        return back()->with('success', 'Rule updated.');
    }

    public function storeLoan(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id', 'kind' => 'required|in:loan,advance',
            'amount' => 'required|numeric|min:1', 'monthly_installment' => 'nullable|numeric|min:0',
            'issued_at' => 'nullable|date', 'reason' => 'nullable|string|max:500',
        ]);
        $data['reference'] = sprintf('LN-%d-%04d', now()->year, EmployeeLoan::whereYear('created_at', now()->year)->count() + 1);
        $data['balance'] = $data['amount'];
        EmployeeLoan::create($data);
        return back()->with('success', 'Loan/advance recorded — repayment will be deducted in payroll.');
    }
}
