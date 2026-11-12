<x-app-layout>
    <x-slot name="header">{{ $asset->name }} <span class="text-muted small">{{ $asset->asset_number }}</span></x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.assets.index') }}" class="btn btn-outline-secondary btn-sm">Register</a></x-slot>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-3"><div class="card-body"><dl class="row small mb-0">
                <dt class="col-4 text-muted">Category</dt><dd class="col-8">{{ $asset->category?->name ?? '—' }}</dd>
                <dt class="col-4 text-muted">Serial / Model</dt><dd class="col-8">{{ $asset->serial_number ?? '—' }} · {{ $asset->brand }} {{ $asset->model }}</dd>
                <dt class="col-4 text-muted">Purchased</dt><dd class="col-8">{{ $asset->purchased_at?->format('d M Y') ?? '—' }} · {{ number_format((float) $asset->purchase_cost) }}</dd>
                <dt class="col-4 text-muted">Est. Book Value</dt><dd class="col-8 fw-semibold">{{ $bookValue !== null ? number_format($bookValue).' TZS' : '—' }} <span class="text-muted fw-normal">({{ str_replace('_',' ',$asset->depreciation_method) }}, mgmt estimate)</span></dd>
                <dt class="col-4 text-muted">Warranty</dt><dd class="col-8">{{ $asset->warranty_expires?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-4 text-muted">Status</dt><dd class="col-8"><span class="badge {{ ['active'=>'text-bg-success','maintenance'=>'text-bg-warning','disposed'=>'text-bg-dark'][$asset->status] }}">{{ $asset->status }}</span> · {{ $asset->condition }}</dd>
                <dt class="col-4 text-muted">Held By</dt><dd class="col-8">{{ $asset->employee?->full_name ?? 'Unassigned' }} · {{ $asset->branch?->name ?? '—' }}</dd>
                @if ($asset->status === 'disposed')<dt class="col-4 text-muted">Disposed</dt><dd class="col-8">{{ $asset->disposed_at?->format('d M Y') }} — {{ $asset->disposal_reason }}</dd>@endif
            </dl></div></div>
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Assignment History</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Action</th><th>Employee</th><th>Branch</th><th>Condition</th><th>By</th><th>Date</th></tr></thead>
                <tbody>@forelse ($asset->assignments->sortByDesc('created_at') as $as)
                    <tr><td><span class="badge text-bg-light border">{{ $as->action }}</span></td><td>{{ $as->employee?->full_name ?? '—' }}</td><td>{{ $as->branch?->name ?? '—' }}</td><td>{{ $as->condition ?? '—' }}</td><td class="small">{{ $as->recorder?->name }}</td><td class="small text-muted">{{ $as->created_at->format('d M Y') }}</td></tr>
                @empty<tr><td colspan="6" class="text-center text-muted py-3">No history.</td></tr>@endforelse</tbody></table></div>
            </div>
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Maintenance</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Type</th><th>Provider</th><th>Done</th><th>Next Due</th><th>Cost</th></tr></thead>
                <tbody>@forelse ($asset->maintenance->sortByDesc('performed_at') as $m)
                    <tr><td><span class="badge text-bg-light border">{{ $m->type }}</span></td><td>{{ $m->provider ?? '—' }}</td><td>{{ $m->performed_at?->format('d M Y') ?? '—' }}</td><td>{{ $m->next_due?->format('d M Y') ?? '—' }}</td><td>{{ $m->cost ? number_format((float) $m->cost) : '—' }}</td></tr>
                @empty<tr><td colspan="5" class="text-center text-muted py-3">No maintenance records.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
        <div class="col-lg-5">
            @if ($asset->status !== 'disposed')
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Assign / Transfer</div><div class="card-body">
                <form method="POST" action="{{ route('admin.assets.assign', $asset) }}" class="row g-2">@csrf
                    <div class="col-12"><label class="form-label small">Employee</label><select name="employee_id" class="form-select"><option value="">— branch only —</option>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small">Branch</label><select name="branch_id" class="form-select"><option value="">—</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label small">Condition</label><select name="condition" class="form-select"><option value="">—</option><option>new</option><option>good</option><option>fair</option><option>poor</option></select></div>
                    <div class="col-12"><input name="notes" class="form-control" placeholder="Handover notes"></div>
                    <div class="col-12"><button class="btn btn-primary w-100">Assign</button></div>
                </form>
                @if ($asset->employee_id)
                    <form method="POST" action="{{ route('admin.assets.return', $asset) }}" class="border-top mt-3 pt-2">@csrf<button class="btn btn-outline-secondary w-100">Record Return</button></form>
                @endif
            </div></div>
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Record Maintenance</div><div class="card-body">
                <form method="POST" action="{{ route('admin.assets.maintenance', $asset) }}" class="row g-2">@csrf
                    <div class="col-6"><select name="type" class="form-select"><option value="repair">Repair</option><option value="service">Service</option><option value="inspection">Inspection</option></select></div>
                    <div class="col-6"><input name="provider" class="form-control" placeholder="Provider"></div>
                    <div class="col-6"><input type="date" name="performed_at" class="form-control" title="Performed"></div>
                    <div class="col-6"><input type="date" name="next_due" class="form-control" title="Next due"></div>
                    <div class="col-12"><input type="number" step="0.01" name="cost" class="form-control" placeholder="Cost"></div>
                    <div class="col-12"><input name="notes" class="form-control" placeholder="Notes"></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">Save</button></div>
                </form>
            </div></div>
            <div class="card shadow-sm border-0 border-danger"><div class="card-body">
                <form method="POST" action="{{ route('admin.assets.dispose', $asset) }}" onsubmit="return confirm('Dispose this asset? This is permanent.')" class="row g-2">@csrf
                    <div class="col-8"><input name="reason" class="form-control" placeholder="Disposal reason" required></div>
                    <div class="col-4"><input type="number" step="0.01" name="disposal_value" class="form-control" placeholder="Value"></div>
                    <div class="col-12"><button class="btn btn-outline-danger w-100">Dispose Asset</button></div>
                </form>
            </div></div>
            @endif
        </div>
    </div>
</x-app-layout>
