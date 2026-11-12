<x-app-layout>
    <x-slot name="header">{{ __('General Ledger') }}</x-slot>
    <x-slot name="headerActions">
        @if (auth()->user()->hasPermission('accounting.periods'))<button class="btn btn-outline-dark btn-sm" data-bs-toggle="offcanvas" data-bs-target="#periodDrawer">Periods</button>@endif
        @if (auth()->user()->hasPermission('accounting.journals'))<button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#jeDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Journal</button>@endif
    </x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Reference</th><th>Date</th><th>Description</th><th>Source</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th>Status</th></tr></thead>
            <tbody>@forelse ($entries as $e)
                <tr><td class="small text-muted">{{ $e->reference }}</td><td>{{ $e->date->format('d M Y') }}</td><td>{{ $e->description }}
                    @foreach ($e->lines as $l)<div class="small text-muted">↳ {{ $l->account->code }} {{ $l->account->name }} — {{ $l->debit > 0 ? 'DR '.number_format((float)$l->debit) : 'CR '.number_format((float)$l->credit) }}</div>@endforeach</td>
                <td><span class="badge text-bg-light border">{{ $e->source_type ?? 'manual' }}</span></td>
                <td class="text-end">{{ number_format($e->lines->sum('debit')) }}</td><td class="text-end">{{ number_format($e->lines->sum('credit')) }}</td>
                <td><span class="badge {{ $e->status==='posted'?'text-bg-success':'text-bg-secondary' }}">{{ $e->status }}</span></td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-4">No journal entries yet.</td></tr>@endforelse</tbody>
        </table></div>
        @if ($entries->hasPages())<div class="card-footer bg-white">{{ $entries->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" id="jeDrawer" style="width:560px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New Journal Entry</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('accounting.journals.store') }}" class="offcanvas-body d-flex flex-column">
            @csrf
            <div class="row g-2 mb-2"><div class="col-4"><label class="form-label small">Date</label><input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-8"><label class="form-label small">Description</label><input name="description" class="form-control" required></div></div>
            @for ($i = 0; $i < 4; $i++)
                <div class="row g-2 mb-1 align-items-center">
                    <div class="col-6"><select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" {{ $i < 2 ? 'required' : '' }}><option value="">Account...</option>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach</select></div>
                    <div class="col-3"><input type="number" step="0.01" name="lines[{{ $i }}][debit]" class="form-control form-control-sm" placeholder="Debit"></div>
                    <div class="col-3"><input type="number" step="0.01" name="lines[{{ $i }}][credit]" class="form-control form-control-sm" placeholder="Credit"></div>
                </div>
            @endfor
            <div class="small text-muted">Debits must equal credits. Posted entries are immutable — corrections need a reversal entry.</div>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Post Journal</button></div>
        </form>
    </div>
    <div class="offcanvas offcanvas-end" id="periodDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Accounting Periods</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('accounting.periods.close') }}" class="row g-2 mb-3" onsubmit="return confirm('Close this period? Posting into it will be blocked.')">@csrf
                <div class="col-6"><select name="month" class="form-select">@for ($m=1;$m<=12;$m++)<option value="{{ $m }}">{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>@endfor</select></div>
                <div class="col-6"><input type="number" name="year" class="form-control" value="{{ now()->year }}"></div>
                <div class="col-12"><button class="btn btn-dark w-100">Close Period</button></div>
            </form>
            <ul class="list-group">@foreach ($periods as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ \Carbon\Carbon::create($p->year,$p->month)->format('M Y') }}</span><span class="badge {{ $p->status==='closed'?'text-bg-dark':'text-bg-success' }}">{{ $p->status }}</span></li>@endforeach</ul>
        </div>
    </div>
</x-app-layout>
