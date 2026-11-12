<x-app-layout>
    <x-slot name="header">{{ __('Front Desk') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#checkinDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Check-In / Walk-In</button></x-slot>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2"><div class="stat-card"><div class="stat-label">Expected</div><div class="stat-value">{{ $stats['expected'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="stat-card"><div class="stat-label">Waiting</div><div class="stat-value text-warning">{{ $stats['waiting'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="stat-card"><div class="stat-label">In Service</div><div class="stat-value text-info">{{ $stats['in_service'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value text-success">{{ $stats['completed'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="stat-card"><div class="stat-label">Walk-ins</div><div class="stat-value">{{ $stats['walkins'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="stat-card"><div class="stat-label">No-shows</div><div class="stat-value text-danger">{{ $stats['no_shows'] }}</div></div></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Live Queue — {{ now()->format('d M Y') }}</div>
                <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>#</th><th>Patient</th><th>Service</th><th>Checked In</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>@forelse ($queue as $q)
                        <tr><td class="fw-semibold">{{ $q->queue_number }}</td>
                        <td>{{ $q->patient->full_name }}{!! $q->is_walkin ? ' <span class="badge text-bg-light border">walk-in</span>' : '' !!}</td>
                        <td>{{ $q->service_label ?? '—' }}</td>
                        <td class="small text-muted">{{ $q->checked_in_at?->format('H:i') }}</td>
                        <td><span class="badge {{ ['waiting'=>'text-bg-warning','called'=>'text-bg-info','in_service'=>'text-bg-primary','completed'=>'text-bg-success','cancelled'=>'text-bg-secondary','no_show'=>'text-bg-danger'][$q->status] }}">{{ str_replace('_',' ',$q->status) }}</span></td>
                        <td class="text-end text-nowrap">
                            @foreach (['waiting'=>'call','called'=>'start','in_service'=>'complete'] as $from=>$act)
                                @if ($q->status === $from)<form method="POST" action="{{ route('reception.queue.update', $q) }}" class="d-inline">@csrf<input type="hidden" name="action" value="{{ $act }}"><button class="btn btn-sm btn-primary">{{ ucfirst(str_replace('_',' ',$act)) }}</button></form>@endif
                            @endforeach
                            @if (in_array($q->status,['waiting','called']))<form method="POST" action="{{ route('reception.queue.update', $q) }}" class="d-inline">@csrf<input type="hidden" name="action" value="no_show"><button class="btn btn-sm btn-outline-danger">No-show</button></form>@endif
                        </td></tr>
                    @empty<tr><td colspan="6" class="text-center text-muted py-4">Queue is empty.</td></tr>@endforelse</tbody>
                </table></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold">Today's Appointments</div>
                <ul class="list-group list-group-flush">
                    @forelse ($appointments as $a)
                        <li class="list-group-item d-flex justify-content-between align-items-center small">
                            <div><strong>{{ $a->scheduled_at->format('H:i') }}</strong> — {{ $a->patient->full_name }}<div class="text-muted">{{ $a->service?->name }}</div></div>
                            <div class="d-flex gap-1 align-items-center">
                                <span class="badge {{ ['confirmed'=>'text-bg-success','scheduled'=>'text-bg-secondary','completed'=>'text-bg-dark','cancelled'=>'text-bg-danger'][$a->status] ?? 'text-bg-light border' }}">{{ $a->status }}</span>
                                @if (in_array($a->status,['scheduled','confirmed']) && !$queue->firstWhere('appointment_id',$a->id))
                                    <form method="POST" action="{{ route('reception.checkin') }}">@csrf<input type="hidden" name="patient_id" value="{{ $a->patient_id }}"><input type="hidden" name="appointment_id" value="{{ $a->id }}"><input type="hidden" name="service_label" value="{{ $a->service?->name }}"><button class="btn btn-sm btn-outline-primary">Check in</button></form>
                                @endif
                            </div>
                        </li>
                    @empty<li class="list-group-item text-muted">No appointments today.</li>@endforelse
                </ul>
            </div>
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Recently Registered</div>
                <ul class="list-group list-group-flush">
                    @foreach ($recentPatients as $p)<li class="list-group-item small d-flex justify-content-between"><span>{{ $p->full_name }}</span><span class="text-muted">{{ $p->patient_number }}</span></li>@endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" id="checkinDrawer" style="width:440px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Check-In / Walk-In</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('reception.checkin') }}" class="offcanvas-body d-flex flex-column gap-2">
            @csrf
            <label class="form-label">Patient *</label>
            <select name="patient_id" class="form-select" required>@foreach (\App\Models\Patient::orderBy('full_name')->get() as $p)<option value="{{ $p->id }}">{{ $p->full_name }} — {{ $p->patient_number }}</option>@endforeach</select>
            <label class="form-label">Service / Reason</label><input name="service_label" class="form-control" placeholder="e.g. Physiotherapy session">
            <p class="small text-muted">No appointment selected → registered as <strong>walk-in</strong>.</p>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Check In</button></div>
        </form>
    </div>
</x-app-layout>
