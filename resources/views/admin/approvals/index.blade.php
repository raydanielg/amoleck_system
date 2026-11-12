@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Expense Approvals') }}</x-slot>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('admin.expense-approvals.index', ['tab' => 'pending']) }}">Pending <span class="badge text-bg-primary ms-1">{{ $pending->total() }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'returned' ? 'active' : '' }}" href="{{ route('admin.expense-approvals.index', ['tab' => 'returned']) }}">Returned <span class="badge text-bg-secondary ms-1">{{ $returned->total() }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'decided' ? 'active' : '' }}" href="{{ route('admin.expense-approvals.index', ['tab' => 'decided']) }}">Decided</a></li>
    </ul>

    @php
        $list = match($tab) { 'returned' => $returned, 'decided' => $decided, default => $pending };
    @endphp
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Reference</th><th>Title</th><th>Category</th><th>Submitted By</th><th class="text-end">Amount</th><th>Date</th><th>Receipt</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($list as $expense)
                        <tr>
                            <td>{{ $expense->expense_reference }}</td>
                            <td>{{ $expense->title }}</td>
                            <td>{{ $expense->category_name_snapshot ?? '—' }}</td>
                            <td>{{ $expense->recorder?->name }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $expense->amount, 2) }}</td>
                            <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td>@if ($expense->receipt_path)<a href="{{ route('expenses.receipt', $expense) }}"><i class="hgi-stroke hgi-attachment-01"></i></a>@else — @endif</td>
                            <td><span class="badge-status status-{{ $expense->status }}">{{ str_replace('_', ' ', $expense->status) }}</span></td>
                            <td><a href="{{ route('admin.expense-approvals.show', $expense) }}" class="btn btn-outline-primary btn-sm">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Nothing here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($list->hasPages())<div class="card-footer bg-white">{{ $list->links() }}</div>@endif
    </div>
</x-app-layout>
