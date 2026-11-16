<x-app-layout>
    <x-slot name="header">{{ __('System Health') }}</x-slot>

    @if ($debugOn)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="hgi-stroke hgi-alert-02"></i>
            <div><strong>APP_DEBUG is ON.</strong> Detailed errors, queries and session data are being exposed.
            Set <code>APP_DEBUG=false</code> in <code>.env</code> then run <code>php artisan config:cache</code> immediately.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Health Checks</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
                        <tbody>
                            @foreach ($checks as $check)
                                <tr>
                                    <td class="fw-semibold">{{ $check['label'] }}</td>
                                    <td><span class="badge {{ $check['ok'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $check['ok'] ? 'OK' : 'ATTENTION' }}</span></td>
                                    <td class="small text-muted">{{ $check['detail'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Environment</div>
                <dl class="row small mb-0 card-body py-2">
                    @foreach ($env as $key => $value)
                        <dt class="col-5 text-muted">{{ $key }}</dt>
                        <dd class="col-7 {{ $key === 'Debug' && $debugOn ? 'text-danger fw-bold' : '' }}">{{ $value }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
