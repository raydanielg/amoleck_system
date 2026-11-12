<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::with('category', 'recorder')->latest('expense_date');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('expense_reference', 'like', "%{$search}%")
                    ->orWhere('payee', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->query('category')) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($from = $request->query('from')) {
            $query->where('expense_date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('expense_date', '<=', $to);
        }

        return view('expenses.index', [
            'expenses' => $query->paginate(15)->withQueryString(),
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'approvalMode' => Settings::bool('expense_approval_mode'),
            'paymentMethods' => Settings::paymentMethods(),
            'receiptsEnabled' => Settings::bool('expense_receipts_enabled', true),
            'currency' => Settings::get('currency', 'TZS'),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        // Staff submission limit (0 = no limit)
        $staffMax = (float) Settings::get('expense_max_staff_amount', '0');
        abort_if(
            $staffMax > 0 && ! $request->user()->isAdmin() && (float) $validated['amount'] > $staffMax,
            422,
            'Expense amount exceeds the staff submission limit. Contact an administrator.'
        );

        $expense = DB::transaction(function () use ($validated, $request) {
            $category = ExpenseCategory::find($validated['expense_category_id']);

            // Approval required? Mode on, and amount above bypass threshold (0 = all need approval)
            $needsApproval = Settings::bool('expense_approval_mode');
            $threshold = (float) Settings::get('expense_approval_threshold', '0');
            if ($needsApproval && $threshold > 0 && (float) $validated['amount'] <= $threshold) {
                $needsApproval = false;
            }

            $expense = Expense::create([
                'expense_reference' => Settings::nextNumber('expense_reference_prefix', 'expense_reference_next', 'EXP'),
                'title' => $validated['title'],
                'expense_category_id' => $category?->id,
                'category_name_snapshot' => $category?->name,
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'description' => $validated['description'] ?? null,
                'payee' => $validated['payee'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'status' => $needsApproval ? Expense::STATUS_SUBMITTED : Expense::STATUS_APPROVED,
                'recorded_by' => $request->user()->id,
            ]);

            if ($request->hasFile('receipt') && Settings::bool('expense_receipts_enabled', true)) {
                $expense->update([
                    'receipt_path' => $request->file('receipt')->store("receipts/{$expense->id}", 'local'),
                ]);
            }

            return $expense;
        });

        AuditLogger::log('expense.created', 'expense', $expense->id, null, $expense->only(['expense_reference', 'title', 'amount', 'status']));

        if ($expense->status === Expense::STATUS_SUBMITTED) {
            \App\Services\NotificationService::notifyAdmins(
                'expense.submitted', 'Expense awaiting approval',
                "{$request->user()->name} submitted {$expense->expense_reference} ({$expense->amount})",
                route('admin.expense-approvals.show', $expense),
            );
        }

        return redirect()->route('expenses.show', $expense)
            ->with('success', "Expense {$expense->expense_reference} recorded.");
    }

    public function show(Expense $expense): View
    {
        $expense->load(['category', 'recorder', 'reviewer']);

        return view('expenses.show', [
            'expense' => $expense,
            'approvalMode' => Settings::bool('expense_approval_mode'),
        ]);
    }

    public function edit(Expense $expense): View
    {
        abort_if($expense->isVoided(), 403, 'Voided expenses cannot be edited.');
        abort_unless($this->canEdit($expense, request()->user()), 403, 'You cannot edit this expense.');

        return view('expenses.edit', ['expense' => $expense, ...$this->formData()]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        abort_if($expense->isVoided(), 403);
        abort_unless($this->canEdit($expense, $request->user()), 403);

        $validated = $this->validateExpense($request);

        $old = $expense->only(['title', 'expense_category_id', 'amount', 'expense_date', 'description', 'payee', 'payment_method', 'status']);

        DB::transaction(function () use ($expense, $validated, $request) {
            // A returned expense goes back to pending approval on update (resubmission)
            $wasReturned = $expense->status === Expense::STATUS_RETURNED;
            if ($wasReturned) {
                abort_unless(Settings::bool('expense_resubmit_allowed', true), 422, 'Resubmission is disabled in approval rules.');
            }
            $category = ExpenseCategory::find($validated['expense_category_id']);

            $expense->update([
                'title' => $validated['title'],
                'expense_category_id' => $category?->id,
                'category_name_snapshot' => $category?->name,
                'amount' => $validated['amount'],
                'expense_date' => $validated['expense_date'],
                'description' => $validated['description'] ?? null,
                'payee' => $validated['payee'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            if ($request->hasFile('receipt') && Settings::bool('expense_receipts_enabled', true)) {
                if ($expense->receipt_path) {
                    Storage::delete($expense->receipt_path);
                }
                $expense->update(['receipt_path' => $request->file('receipt')->store("receipts/{$expense->id}", 'local')]);
            }

            if ($wasReturned) {
                $expense->update([
                    'status' => Expense::STATUS_SUBMITTED,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
                \App\Models\ExpenseApproval::create([
                    'expense_id' => $expense->id,
                    'reviewer_id' => $request->user()->id,
                    'decision' => \App\Models\ExpenseApproval::DECISION_RESUBMITTED,
                    'previous_status' => Expense::STATUS_RETURNED,
                    'new_status' => Expense::STATUS_SUBMITTED,
                ]);
                \App\Services\NotificationService::notifyAdmins(
                    'expense.submitted', 'Expense resubmitted',
                    "{$request->user()->name} resubmitted {$expense->expense_reference}",
                    route('admin.expense-approvals.show', $expense),
                );
            }
        });

        AuditLogger::log('expense.updated', 'expense', $expense->id, $old, $expense->fresh()->only(array_keys($old)));

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function void(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate(['void_reason' => ['required', 'string', 'max:500']]);
        abort_if($expense->isVoided(), 422);

        $expense->update([
            'status' => Expense::STATUS_VOIDED,
            'void_reason' => $validated['void_reason'],
        ]);

        AuditLogger::log('expense.voided', 'expense', $expense->id, null, ['reason' => $validated['void_reason']]);

        return back()->with('success', 'Expense voided.');
    }

    public function receipt(Expense $expense): StreamedResponse
    {
        abort_unless($expense->receipt_path && Storage::exists($expense->receipt_path), 404);

        return Storage::download($expense->receipt_path, basename($expense->receipt_path));
    }

    private function canEdit(Expense $expense, $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $expense->recorded_by === $user->id
            && in_array($expense->status, [Expense::STATUS_SUBMITTED, Expense::STATUS_RETURNED], true);
    }

    private function validateExpense(Request $request): array
    {
        $receiptsEnabled = Settings::bool('expense_receipts_enabled', true);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'payee' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'receipt' => [$receiptsEnabled ? (Settings::bool('expense_receipt_required') ? 'required' : 'nullable') : 'exclude', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
    }

    private function formData(): array
    {
        return [
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'paymentMethods' => Settings::paymentMethods(),
            'receiptsEnabled' => Settings::bool('expense_receipts_enabled', true),
            'currency' => Settings::get('currency', 'TZS'),
        ];
    }
}
