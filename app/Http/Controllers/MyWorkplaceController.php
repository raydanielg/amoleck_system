<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollItem;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MyWorkplaceController extends Controller
{
    private function employeeOrFail()
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'Your account is not linked to an employee record.');
        return $emp;
    }

    public function index()
    {
        $emp = auth()->user()->employee;
        return view('my.workplace', [
            'employee' => $emp?->load('department', 'jobPosition', 'branch', 'assets'),
            'today' => $emp ? AttendanceRecord::where('employee_id', $emp->id)->whereDate('date', today())->first() : null,
            'attendance' => $emp ? $emp->attendance()->latest('date')->limit(15)->get() : collect(),
            'leaveTypes' => LeaveType::where('is_active', true)->get(),
            'leaveRequests' => $emp ? $emp->leaveRequests()->with('leaveType')->latest()->limit(10)->get() : collect(),
            'payslips' => $emp ? PayrollItem::with('batch')->where('employee_id', $emp->id)->whereHas('batch', fn ($q) => $q->whereIn('status', ['paid', 'closed']))->latest()->limit(12)->get() : collect(),
            'requests' => $emp ? HrRequest::where('employee_id', $emp->id)->latest()->limit(10)->get() : collect(),
        ]);
    }

    public function clockIn()
    {
        abort_unless(auth()->user()->hasPermission('self.attendance'), 403);
        $emp = $this->employeeOrFail();
        $existing = AttendanceRecord::where('employee_id', $emp->id)->whereDate('date', today())->first();
        if ($existing && $existing->check_in) {
            throw ValidationException::withMessages(['clock' => 'You have already clocked in today.']);
        }
        $lateThreshold = \App\Support\Settings::get('work_start_time', '08:30');
        $grace = (int) \App\Support\Settings::int('late_grace_minutes', 15);
        $status = now()->format('H:i') > now()->parse($lateThreshold)->addMinutes($grace)->format('H:i') ? 'late' : 'present';
        AttendanceRecord::updateOrCreate(
            ['employee_id' => $emp->id, 'date' => today()],
            ['check_in' => now()->format('H:i'), 'status' => $status, 'recorded_by' => auth()->id()]
        );
        AuditLogger::log('attendance.clock_in', 'employee', $emp->id);
        return back()->with('success', 'Clocked in at '.now()->format('H:i'));
    }

    public function clockOut()
    {
        abort_unless(auth()->user()->hasPermission('self.attendance'), 403);
        $emp = $this->employeeOrFail();
        $rec = AttendanceRecord::where('employee_id', $emp->id)->whereDate('date', today())->first();
        if (! $rec || ! $rec->check_in) {
            throw ValidationException::withMessages(['clock' => 'You must clock in first.']);
        }
        if ($rec->check_out) {
            throw ValidationException::withMessages(['clock' => 'You have already clocked out.']);
        }
        $rec->update(['check_out' => now()->format('H:i')]);
        AuditLogger::log('attendance.clock_out', 'employee', $emp->id);
        return back()->with('success', 'Clocked out at '.now()->format('H:i'));
    }

    public function submitLeave(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('self.leave'), 403);
        $emp = $this->employeeOrFail();
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);
        $days = (int) now()->parse($data['start_date'])->diffInDays($data['end_date']) + 1;
        LeaveRequest::create($data + ['employee_id' => $emp->id, 'days' => $days]);
        return back()->with('success', 'Leave request submitted.');
    }

    public function submitRequest(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('self.requests'), 403);
        $emp = $this->employeeOrFail();
        $data = $request->validate([
            'type' => 'required|in:info_update,attendance_correction,document,other',
            'subject' => 'required|string|max:150', 'details' => 'nullable|string|max:1000',
        ]);
        HrRequest::create($data + ['employee_id' => $emp->id]);
        return back()->with('success', 'Request submitted to HR.');
    }

    /** Own payslip only — enforced via employee ownership. */
    public function payslip(PayrollItem $payrollItem)
    {
        abort_unless(auth()->user()->hasPermission('self.payslip'), 403);
        $emp = $this->employeeOrFail();
        abort_unless($payrollItem->employee_id === $emp->id, 403, 'This payslip is not yours.');
        abort_unless(in_array($payrollItem->batch->status, ['paid', 'closed']), 403, 'Payslip not yet released.');
        $payrollItem->load('employee.department', 'employee.jobPosition', 'batch');
        return Pdf::loadView('pdf.payslip', ['item' => $payrollItem, 'clinic' => ['clinic_name' => \App\Support\Settings::get('clinic_name')]])->stream();
    }
}
