<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>
    <x-slot name="headerActions">
        <div class="d-flex gap-2">
            <a href="{{ route('reports.export', [$type, 'csv', 'from' => $from, 'to' => $to] + request()->query()) }}" class="btn btn-outline-secondary btn-sm">CSV</a>
            <a href="{{ route('reports.export', [$type, 'pdf', 'from' => $from, 'to' => $to] + request()->query()) }}" class="btn btn-outline-secondary btn-sm">PDF</a>
        </div>
    </x-slot>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                @if ($type === 'expenses')
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            @foreach (\App\Models\ExpenseCategory::orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            @foreach (['submitted','approved','rejected','voided'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($type === 'visits')
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Staff Member</label>
                        <select name="staff" class="form-select">
                            <option value="">All</option>
                            @foreach (\App\Models\User::orderBy('name')->get() as $u)
                                <option value="{{ $u->id }}" @selected(request('staff') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-1">
                    <button class="btn btn-primary w-100">Run</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        @if (!empty($totals))
            <div class="card-header bg-white d-flex gap-4">
                @foreach ($totals as $total)
                    <span><span class="text-muted small">{{ $total['label'] }}:</span> <strong>{{ $total['value'] }}</strong></span>
                @endforeach
            </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        @foreach ($columns as $col)<th>{{ $col }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No data for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">
            Generated {{ now()->format('d/m/Y H:i') }} by {{ Auth::user()->name }} · Period {{ $from }} — {{ $to }}
        </div>
    </div>
</x-app-layout>
