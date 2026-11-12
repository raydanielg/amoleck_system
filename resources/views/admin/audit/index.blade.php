<x-app-layout>
    <x-slot name="header">{{ __('Audit Log') }}</x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search action or entity..."></div>
                <div class="col-md-3">
                    <select name="user" class="form-select">
                        <option value="">All users</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
                <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
                <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td><code class="small">{{ $log->action }}</code></td>
                            <td class="small">{{ $log->entity_type }}{{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
                            <td class="small text-muted" style="max-width: 350px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                @if ($log->new_values){{ json_encode($log->new_values) }}@endif
                            </td>
                            <td class="small">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No audit events.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())<div class="card-footer bg-white">{{ $logs->links() }}</div>@endif
    </div>
</x-app-layout>
