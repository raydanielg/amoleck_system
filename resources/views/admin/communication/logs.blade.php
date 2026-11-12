<x-app-layout>
    <x-slot name="header">{{ __('Message Logs') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#composeDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Compose SMS</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Phone, recipient or reference..."></div>
                <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (['queued','scheduled','submitted','sent','delivered','failed','cancelled'] as $st)<option @selected(request('status') === $st)>{{ $st }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="type" class="form-select"><option value="">All types</option>@foreach (['manual','automatic','campaign','test'] as $t)<option @selected(request('type') === $t)>{{ $t }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Reference</th><th>Recipient</th><th>Message</th><th>Type</th><th>Seg/Cost</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse ($messages as $m)
                        <tr>
                            <td class="small text-muted">{{ $m->message_reference }}</td>
                            <td>{{ $m->recipient_name ?? '—' }}<div class="small text-muted">{{ $m->phone }}</div></td>
                            <td><div class="small text-truncate" style="max-width:220px">{{ $m->body }}</div></td>
                            <td><span class="badge text-bg-light border">{{ $m->type }}</span></td>
                            <td class="small text-muted">{{ $m->segments }}{{ $m->cost ? ' · '.number_format((float) $m->cost, 2) : '' }}</td>
                            <td><span class="badge {{ ['delivered'=>'text-bg-success','failed'=>'text-bg-danger','sent'=>'text-bg-info','submitted'=>'text-bg-info','queued'=>'text-bg-warning','scheduled'=>'text-bg-secondary'][$m->status] ?? 'text-bg-light border text-dark' }}">{{ $m->status }}</span></td>
                            <td class="small text-muted">{{ $m->created_at->format('d M H:i') }}</td>
                            <td class="text-end"><a href="{{ route('admin.communication.messages.show', $m) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No messages.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())<div class="card-footer bg-white">{{ $messages->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="composeDrawer" style="width:480px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Compose SMS</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.communication.messages.store') }}" class="offcanvas-body d-flex flex-column">
            @csrf
            <div class="small text-muted mb-2">Sender ID: <strong>{{ $senderId ?: 'not configured' }}</strong></div>
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-select mb-2" id="cPatient"><option value="">—</option>@foreach ($patients as $p)<option value="{{ $p->id }}" data-phone="{{ $p->phone }}">{{ $p->full_name }}</option>@endforeach</select>
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select mb-2" id="cCustomer"><option value="">—</option>@foreach ($customers as $c)<option value="{{ $c->id }}" data-phone="{{ $c->phone }}">{{ $c->name }}</option>@endforeach</select>
            <label class="form-label">Phone Number</label><input name="phone" id="cPhone" class="form-control mb-2" placeholder="0712 345 678">
            <label class="form-label">Template</label>
            <select id="cTemplate" class="form-select mb-2"><option value="">—</option>@foreach ($templates as $t)<option value="{{ $t->id }}" data-body="{{ $t->body }}">[{{ $t->category }}] {{ $t->name }}</option>@endforeach</select>
            <input type="hidden" name="template_id" id="cTemplateId">
            <label class="form-label">Message <span id="charCount" class="text-muted small"></span></label>
            <textarea name="body" id="cBody" class="form-control mb-2" rows="4" required maxlength="1600"></textarea>
            <label class="form-label">Schedule (optional)</label><input type="datetime-local" name="scheduled_at" class="form-control">
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Send Message</button></div>
        </form>
    </div>
    <script>
        const body = document.getElementById('cBody');
        body.addEventListener('input', () => {
            const n = body.value.length, seg = Math.max(1, Math.ceil(n / 160));
            document.getElementById('charCount').textContent = `(${n} chars · ~${seg} SMS)`;
        });
        document.getElementById('cTemplate').addEventListener('change', e => {
            const o = e.target.selectedOptions[0];
            if (o && o.dataset.body) { body.value = o.dataset.body; body.dispatchEvent(new Event('input')); }
            document.getElementById('cTemplateId').value = e.target.value;
        });
        [['cPatient','patient_id'],['cCustomer','customer_id']].forEach(([id]) => {
            document.getElementById(id).addEventListener('change', e => {
                const ph = e.target.selectedOptions[0]?.dataset.phone;
                if (ph) document.getElementById('cPhone').value = ph;
            });
        });
    </script>
</x-app-layout>
