<x-app-layout>
    <x-slot name="header">{{ __('Bulk Campaigns') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#campDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Campaign</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Name</th><th>Audience</th><th>Recipients</th><th>Status</th><th>Scheduled</th><th>Created</th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->name }}<div class="small text-muted fw-normal text-truncate" style="max-width:260px">{{ $c->body }}</div></td>
                            <td><span class="badge text-bg-light border">{{ str_replace('_', ' ', $c->audience) }}</span></td>
                            <td>{{ $c->recipients_count }}</td>
                            <td><span class="badge {{ ['sent'=>'text-bg-success','sending'=>'text-bg-info','scheduled'=>'text-bg-warning','draft'=>'text-bg-secondary','cancelled'=>'text-bg-secondary'][$c->status] ?? 'text-bg-light' }}">{{ $c->status }}</span></td>
                            <td class="text-muted">{{ $c->scheduled_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-muted">{{ $c->created_at->format('d M Y') }} · {{ $c->creator?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No campaigns.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campaigns->hasPages())<div class="card-footer bg-white">{{ $campaigns->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="campDrawer" style="width:460px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New Campaign</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.communication.campaigns.store') }}" class="offcanvas-body d-flex flex-column" onsubmit="return confirm('Send this campaign? Recipients who opted out are skipped automatically.')">
            @csrf
            <label class="form-label">Name *</label><input name="name" class="form-control mb-2" required>
            <label class="form-label">Audience *</label>
            <select name="audience" class="form-select mb-2"><option value="all_patients">All patients</option><option value="recent_patients">Patients (last 30 days)</option><option value="followups">Patients with upcoming follow-ups</option><option value="customers">Customers</option></select>
            <label class="form-label">Message *</label><textarea name="body" class="form-control mb-2" rows="5" required maxlength="1600"></textarea>
            <label class="form-label">When</label>
            <select name="send" class="form-select mb-2"><option value="now">Send now</option><option value="schedule">Schedule (messages stay queued)</option></select>
            <input type="datetime-local" name="scheduled_at" class="form-control">
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create Campaign</button></div>
        </form>
    </div>
</x-app-layout>
