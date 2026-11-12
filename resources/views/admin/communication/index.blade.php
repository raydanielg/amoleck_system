<x-app-layout>
    <x-slot name="header">{{ __('Communication Center') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.communication.messages') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>Compose</a>
        <a href="{{ route('admin.communication.gateway') }}" class="btn btn-outline-secondary btn-sm">Gateway</a>
    </x-slot>
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Messages (period)</div><div class="stat-value">{{ $stats['total'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Delivered</div><div class="stat-value text-success">{{ $stats['delivered'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value text-warning">{{ $stats['pending'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Failed</div><div class="stat-value text-danger">{{ $stats['failed'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Today</div><div class="stat-value">{{ $stats['today'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">This Month</div><div class="stat-value">{{ $stats['month'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Est. Cost</div><div class="stat-value">{{ number_format((float) $stats['cost'], 2) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Success Rate</div><div class="stat-value">{{ $stats['success_rate'] !== null ? $stats['success_rate'].'%' : '—' }}</div></div></div>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Gateway Status</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Provider</dt><dd class="col-7">{{ $gateway->label ?: $gateway->provider }}</dd>
                        <dt class="col-5 text-muted">Status</dt><dd class="col-7"><span class="badge {{ $gateway->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $gateway->enabled ? 'Enabled' : 'Disabled' }}</span></dd>
                        <dt class="col-5 text-muted">Configured</dt><dd class="col-7">{{ $gateway->isConfigured() ? 'Yes' : 'Not configured' }}</dd>
                        <dt class="col-5 text-muted">Sender ID</dt><dd class="col-7">{{ $gateway->sender_id ?: '—' }}</dd>
                        <dt class="col-5 text-muted">Balance</dt><dd class="col-7">{{ $gateway->manual_balance !== null ? number_format((float) $gateway->manual_balance, 2).' (manual)' : '—' }}</dd>
                    </dl>
                    <a href="{{ route('admin.communication.gateway') }}" class="btn btn-sm btn-outline-primary w-100 mt-3">Configure Gateway</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Recent Messages</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recent as $m)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><a href="{{ route('admin.communication.messages.show', $m) }}" class="fw-semibold">{{ $m->message_reference }}</a> <span class="text-muted small">→ {{ $m->phone }}</span><div class="small text-muted text-truncate" style="max-width:400px">{{ $m->body }}</div></div>
                            <span class="badge {{ ['delivered'=>'text-bg-success','failed'=>'text-bg-danger','sent'=>'text-bg-info','submitted'=>'text-bg-info','queued'=>'text-bg-warning','scheduled'=>'text-bg-secondary'][$m->status] ?? 'text-bg-light border text-dark' }}">{{ $m->status }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No messages yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
