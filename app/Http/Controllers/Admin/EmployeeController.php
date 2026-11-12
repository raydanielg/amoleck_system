<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosition;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with('department', 'jobPosition', 'branch')
            ->when($request->q, fn ($q, $v) => $q->where('full_name', 'like', "%$v%")->orWhere('employee_number', 'like', "%$v%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.hr.employees', [
            'employees' => $employees,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'positions' => JobPosition::where('is_active', true)->orderBy('title')->get(),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:200', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:200', 'gender' => 'nullable|string|max:20',
            'dob' => 'nullable|date', 'address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:200',
            'department_id' => 'nullable|exists:departments,id',
            'job_position_id' => 'nullable|exists:job_positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'branch_id' => 'nullable|exists:branches,id',
            'hired_at' => 'nullable|date', 'contract_start' => 'nullable|date', 'contract_end' => 'nullable|date|after_or_equal:contract_start',
            'basic_salary' => 'nullable|numeric|min:0', 'bank_details' => 'nullable|string|max:500',
        ]);
        $data['employee_number'] = sprintf('EMP-%d-%06d', now()->year, Employee::whereYear('created_at', now()->year)->count() + 1);
        $emp = Employee::create($data);
        AuditLogger::log('employee.created', 'employee', $emp->id);
        return back()->with('success', "Employee {$emp->employee_number} registered.");
    }

    public function show(Employee $employee)
    {
        $employee->load('department', 'jobPosition', 'branch', 'components', 'loans.repayments', 'leaveRequests.leaveType', 'assets');
        return view('admin.hr.employee-show', [
            'employee' => $employee,
            'attendance' => $employee->attendance()->latest('date')->limit(15)->get(),
            'leaveTypes' => LeaveType::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:200', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:200', 'address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:200',
            'department_id' => 'nullable|exists:departments,id',
            'job_position_id' => 'nullable|exists:job_positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'branch_id' => 'nullable|exists:branches,id',
            'contract_start' => 'nullable|date', 'contract_end' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0', 'bank_details' => 'nullable|string|max:500',
            'status' => 'required|in:active,suspended,terminated,resigned',
        ]);
        $employee->update($data);
        AuditLogger::log('employee.updated', 'employee', $employee->id);
        return back()->with('success', 'Employee updated.');
    }

    public function storeComponent(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'kind' => 'required|in:allowance,bonus',
            'amount' => 'required|numeric|min:0',
        ]);
        $employee->components()->create($data);
        return back()->with('success', 'Salary component added.');
    }

    public function recordAttendance(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id', 'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i', 'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,late,absent,leave,half_day', 'notes' => 'nullable|string|max:255',
        ]);
        AttendanceRecord::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date']],
            array_merge($data, ['recorded_by' => auth()->id()])
        );
        return back()->with('success', 'Attendance recorded.');
    }

    public function submitLeave(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);
        $days = (int) now()->parse($data['start_date'])->diffInDays($data['end_date']) + 1;
        $employee->leaveRequests()->create($data + ['days' => $days]);
        return back()->with('success', 'Leave request submitted.');
    }

    public function decideLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected']);
        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This request has already been decided.']);
        }
        $leaveRequest->update(['status' => $data['decision'], 'decided_by' => auth()->id(), 'decided_at' => now()]);
        AuditLogger::log('leave.'.$data['decision'], 'leave_request', $leaveRequest->id);
        return back()->with('success', 'Leave '.$data['decision'].'.');
    }

    public function storeDepartment(Request $request)
    {
        Department::create($request->validate(['name' => 'required|string|max:100|unique:departments,name']));
        return back()->with('success', 'Department added.');
    }

    public function storePosition(Request $request)
    {
        JobPosition::create($request->validate(['title' => 'required|string|max:100|unique:job_positions,title']));
        return back()->with('success', 'Position added.');
    }
}
