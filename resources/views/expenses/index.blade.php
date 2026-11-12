<x-app-layout>
    <x-slot name="header">{{ __('Expenses') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#expenseDrawer">
            <i class="hgi-stroke hgi-plus-sign me-1"></i>{{ __('Add Expense') }}
        </button>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search title, ref, payee...">
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach (['submitted', 'approved', 'rejected', 'voided'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
                <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
                <div class="col-md-1"><button class="btn btn-outline-secondary w-100"><i class="hgi-stroke hgi-search-01"></i></button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Reference</th><th>Date</th><th>Title</th><th>Category</th><th class="text-end">Amount</th><th>Method</th><th>Status</th><th>By</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td class="fw-semibold">{{ $expense->expense_reference }}</td>
                            <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td><a href="{{ route('expenses.show', $expense) }}">{{ $expense->title }}</a></td>
                            <td>{{ $expense->category_name_snapshot ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $expense->amount, 2) }}</td>
                            <td class="small">{{ $expense->payment_method ?? '—' }}</td>
                            <td>
                                <span class="badge {{ ['approved' => 'text-bg-success', 'submitted' => 'text-bg-warning', 'rejected' => 'text-bg-danger', 'voided' => 'text-bg-secondary'][$expense->status] ?? 'text-bg-light' }}">
                                    {{ ucfirst($expense->status) }}
                                </span>
                            </td>
                            <td class="small">{{ $expense->recorder?->name }}</td>
                            <td class="text-end"><a href="{{ route('expenses.show', $expense) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No expenses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="card-footer bg-white">{{ $expenses->links() }}</div>
        @endif
    </div>

    {{-- Add Expense Drawer --}}
    <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="expenseDrawer" data-bs-scroll="true">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-semibold">Record Expense</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                @include('expenses._form', ['expense' => null])
            </div>
            <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                <button type="submit" class="btn btn-primary flex-grow-1">Save Expense</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</x-app-layout>
