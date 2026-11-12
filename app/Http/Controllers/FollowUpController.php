<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $query = FollowUp::with('patient', 'visit', 'recorder')->orderBy('due_date');

        if ($search = trim((string) $request->query('q'))) {
            $query->whereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%")
                ->orWhere('patient_number', 'like', "%{$search}%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        match ($request->query('due')) {
            'today' => $query->whereDate('due_date', '<=', today())->where('status', FollowUp::STATUS_PENDING),
            'upcoming' => $query->where('due_date', '>', today())->where('status', FollowUp::STATUS_PENDING),
            'overdue' => $query->where('due_date', '<', today())->where('status', FollowUp::STATUS_PENDING),
            default => null,
        };

        return view('followups.index', [
            'followUps' => $query->paginate(15)->withQueryString(),
            'patients' => Patient::where('status', Patient::STATUS_ACTIVE)->orderBy('full_name')->get(['id', 'full_name', 'patient_number']),
            'overdueCount' => FollowUp::where('due_date', '<', today())->where('status', FollowUp::STATUS_PENDING)->count(),
            'dueTodayCount' => FollowUp::whereDate('due_date', '<=', today())->where('status', FollowUp::STATUS_PENDING)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $followUp = FollowUp::create([
            ...$validated,
            'status' => FollowUp::STATUS_PENDING,
            'recorded_by' => $request->user()->id,
        ]);

        AuditLogger::log('followup.created', 'follow_up', $followUp->id, null, $followUp->only(['patient_id', 'due_date']));

        return back()->with('success', 'Follow-up recorded.');
    }

    public function updateStatus(Request $request, FollowUp $followUp): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([FollowUp::STATUS_RETURNED, FollowUp::STATUS_MISSED, FollowUp::STATUS_CANCELLED, FollowUp::STATUS_PENDING])],
            'outcome_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $followUp->status;
        $followUp->update([
            'status' => $validated['status'],
            'outcome_notes' => $validated['outcome_notes'] ?? $followUp->outcome_notes,
            'completed_at' => $validated['status'] !== FollowUp::STATUS_PENDING ? now() : null,
        ]);

        AuditLogger::log('followup.status_changed', 'follow_up', $followUp->id, ['status' => $old], ['status' => $validated['status']]);

        return back()->with('success', 'Follow-up updated.');
    }
}
