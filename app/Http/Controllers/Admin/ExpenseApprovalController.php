<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseApproval;
use App\Models\ExpenseComment;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pending');

        $pending = Expense::with('category', 'recorder')
            ->where('status', Expense::STATUS_SUBMITTED)
            ->latest('created_at')->paginate(15, ['*'], 'pending_page');

        $returned = Expense::with('category', 'recorder')
            ->where('status', Expense::STATUS_RETURNED)
            ->latest('reviewed_at')->paginate(15, ['*'], 'returned_page');

        $decided = Expense::with('category', 'recorder', 'reviewer')
            ->whereIn('status', [Expense::STATUS_APPROVED, Expense::STATUS_REJECTED, Expense::STATUS_PAID])
            ->latest('reviewed_at')->paginate(15, ['*'], 'decided_page');

        return view('admin.approvals.index', compact('tab', 'pending', 'returned', 'decided'));
    }

    public function show(Expense $expense): View
    {
        $expense->load(['category', 'recorder', 'reviewer', 'payer', 'approvals.reviewer', 'comments.user']);

        return view('admin.approvals.show', [
            'expense' => $expense,
            'canDecide' => $this->canDecide($expense, request()->user()),
            'paymentStep' => Settings::bool('expense_payment_step_enabled'),
        ]);
    }

    public function decide(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject,return,mark_paid'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if ($expense->recorded_by === $user->id) {
            throw ValidationException::withMessages([
                'decision' => 'You cannot approve or reject an expense you submitted. Another administrator must review it.',
            ]);
        }

        $decision = $validated['decision'];
        $reason = $validated['reason'] ?? null;

        if (in_array($decision, ['reject', 'return']) && ! $reason) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for this decision.']);
        }

        DB::transaction(function () use ($expense, $decision, $reason, $user) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $previous = $expense->status;

            match ($decision) {
                'approve' => $this->applyDecision($expense, Expense::STATUS_SUBMITTED, Expense::STATUS_APPROVED, ExpenseApproval::DECISION_APPROVED, $reason, $user),
                'reject' => $this->applyDecision($expense, Expense::STATUS_SUBMITTED, Expense::STATUS_REJECTED, ExpenseApproval::DECISION_REJECTED, $reason, $user),
                'return' => $this->applyDecision($expense, Expense::STATUS_SUBMITTED, Expense::STATUS_RETURNED, ExpenseApproval::DECISION_RETURNED, $reason, $user),
                'mark_paid' => $this->applyDecision($expense, Expense::STATUS_APPROVED, Expense::STATUS_PAID, ExpenseApproval::DECISION_MARKED_PAID, $reason, $user),
                default => throw ValidationException::withMessages(['decision' => 'Invalid decision.']),
            };
        });

        $expense->load('recorder');
        if ($expense->recorder) {
            NotificationService::notify(
                $expense->recorder, 'expense.decision',
                "Expense {$decision}",
                "Your expense {$expense->expense_reference} was {$decision}".($reason ? ": {$reason}" : ''),
                route('expenses.show', $expense),
            );
        }

        return back()->with('success', "Expense {$decision} recorded.");
    }

    private function applyDecision(Expense $expense, string $expected, string $newStatus, string $logDecision, ?string $reason, $user): void
    {
        if ($expense->status !== $expected) {
            throw ValidationException::withMessages([
                'decision' => "Expense is '{$expense->status}' — this action is not available.",
            ]);
        }

        $previous = $expense->status;
        $expense->status = $newStatus;
        $expense->reviewed_by = $user->id;
        $expense->reviewed_at = now();

        if ($newStatus === Expense::STATUS_REJECTED) {
            $expense->rejection_reason = $reason;
        }
        if ($newStatus === Expense::STATUS_RETURNED) {
            $expense->returned_reason = $reason;
        }
        if ($newStatus === Expense::STATUS_PAID) {
            $expense->paid_at = now();
            $expense->paid_by = $user->id;
            try {
                \App\Services\AccountingService::post('EXP-'.$expense->expense_reference, 'Expense '.$expense->expense_reference.' — '.$expense->title, [
                    ['account' => \App\Services\AccountingService::EXPENSE, 'debit' => $expense->amount],
                    ['account' => \App\Services\AccountingService::CASH, 'credit' => $expense->amount],
                ], 'expense', $expense->id, $expense->expense_date);
            } catch (\Throwable) {}
        }
        $expense->save();

        ExpenseApproval::create([
            'expense_id' => $expense->id,
            'reviewer_id' => $user->id,
            'decision' => $logDecision,
            'reason' => $reason,
            'previous_status' => $previous,
            'new_status' => $newStatus,
        ]);

        AuditLogger::log("expense.{$logDecision}", 'expense', $expense->id,
            ['status' => $previous], ['status' => $newStatus, 'reason' => $reason]);
    }

    public function comment(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate(['comment' => ['required', 'string', 'max:1000']]);

        ExpenseComment::create([
            'expense_id' => $expense->id,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    private function canDecide(Expense $expense, $user): bool
    {
        if ($expense->recorded_by === $user->id) {
            return false;
        }

        return in_array($expense->status, [Expense::STATUS_SUBMITTED, Expense::STATUS_APPROVED], true);
    }
}
