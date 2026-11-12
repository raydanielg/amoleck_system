<x-app-layout>
    <x-slot name="header">Expense {{ $expense->expense_reference }}</x-slot>
    <x-slot name="headerActions">
        @if (! $expense->isVoided() && (Auth::user()->isAdmin() || ($expense->recorded_by === Auth::id() && $expense->status === 'submitted')))
            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
        @endif
    </x-slot>

    <div class="card shadow-sm border-0" style="max-width: 800px;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Expense Details</span>
            <span class="badge {{ ['approved' => 'text-bg-success', 'submitted' => 'text-bg-warning', 'rejected' => 'text-bg-danger', 'voided' => 'text-bg-secondary'][$expense->status] ?? 'text-bg-light' }}">{{ ucfirst($expense->status) }}</span>
        </div>
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-4 text-muted">Reference</dt><dd class="col-8">{{ $expense->expense_reference }}</dd>
                <dt class="col-4 text-muted">Title</dt><dd class="col-8">{{ $expense->title }}</dd>
                <dt class="col-4 text-muted">Category</dt><dd class="col-8">{{ $expense->category_name_snapshot ?? '—' }}</dd>
                <dt class="col-4 text-muted">Amount</dt><dd class="col-8 fw-bold fs-6">{{ \App\Support\Settings::get('currency', 'TZS') }} {{ number_format((float) $expense->amount, 2) }}</dd>
                <dt class="col-4 text-muted">Date</dt><dd class="col-8">{{ $expense->expense_date->format('d/m/Y') }}</dd>
                <dt class="col-4 text-muted">Payee</dt><dd class="col-8">{{ $expense->payee ?? '—' }}</dd>
                <dt class="col-4 text-muted">Method</dt><dd class="col-8">{{ $expense->payment_method ?? '—' }}</dd>
                <dt class="col-4 text-muted">Description</dt><dd class="col-8" style="white-space: pre-wrap;">{{ $expense->description ?? '—' }}</dd>
                <dt class="col-4 text-muted">Receipt</dt>
                <dd class="col-8">
                    @if ($expense->receipt_path)
                        <a href="{{ route('expenses.receipt', $expense) }}">Download receipt</a>
                    @else — @endif
                </dd>
                <dt class="col-4 text-muted">Recorded By</dt><dd class="col-8">{{ $expense->recorder?->name ?? '—' }}</dd>
                @if ($expense->reviewer)
                    <dt class="col-4 text-muted">Reviewed By</dt><dd class="col-8">{{ $expense->reviewer->name }} · {{ $expense->reviewed_at?->format('d/m/Y H:i') }}</dd>
                @endif
                @if ($expense->rejection_reason)
                    <dt class="col-4 text-muted">Rejection Reason</dt><dd class="col-8">{{ $expense->rejection_reason }}</dd>
                @endif
                @if ($expense->returned_reason)
                    <dt class="col-4 text-muted">Returned Reason</dt><dd class="col-8 text-warning">{{ $expense->returned_reason }} — edit and save to resubmit.</dd>
                @endif
                @if ($expense->paid_at)
                    <dt class="col-4 text-muted">Paid</dt><dd class="col-8">{{ $expense->paid_at->format('d/m/Y H:i') }} by {{ $expense->payer?->name }}</dd>
                @endif
                @if ($expense->void_reason)
                    <dt class="col-4 text-muted">Void Reason</dt><dd class="col-8">{{ $expense->void_reason }}</dd>
                @endif
            </dl>
        </div>

        @if (Auth::user()->isAdmin() && ! $expense->isVoided())
            <div class="card-footer bg-white d-flex flex-wrap gap-2 align-items-center">
                @if (in_array($expense->status, ['submitted', 'approved', 'returned']))
                    <a href="{{ route('admin.expense-approvals.show', $expense) }}" class="btn btn-primary btn-sm">Open in Approval Center</a>
                @endif
                <form method="POST" action="{{ route('admin.expenses.void', $expense) }}" class="d-flex gap-2" onsubmit="return confirm('Void this expense?');">
                    @csrf
                    <input type="text" name="void_reason" class="form-control form-control-sm" placeholder="Void reason" required style="width: 200px;">
                    <button class="btn btn-outline-secondary btn-sm">Void</button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
