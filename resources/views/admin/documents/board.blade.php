<x-app-layout>
    <x-slot name="header">{{ __('Document Board & Announcements') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#composer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Announcement</button>
        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">File Manager</a>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Audience</th><th>Ack</th><th>Published</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($announcements as $a)
                        @php
                            $t = $a->targets ?? [];
                            $parts = collect([count($t['roles']??[]).' roles', count($t['departments']??[]).' depts', count($t['branches']??[]).' branches', count($t['users']??[]).' users'])->filter(fn($x)=>$x[0]!=='0')->implode(', ');
                            $aud = !empty($t['all']) ? 'All employees' : ($parts ?: '—');
                            $acks = $a->receipts()->where('status','acknowledged')->count();
                            $total = $a->receipts()->count();
                        @endphp
                        <tr>
                            <td><span class="fw-semibold">{{ $a->title }}</span>@if ($a->pinned) <i class="hgi-stroke hgi-pin text-primary"></i>@endif<br><span class="small text-muted">{{ Str::limit($a->summary,60) }}</span></td>
                            <td><span class="badge text-bg-light">{{ $a->category }}</span></td>
                            <td><span class="badge text-bg-{{ ['normal'=>'secondary','important'=>'primary','high'=>'warning','critical'=>'danger'][$a->priority] }}">{{ ucfirst($a->priority) }}</span></td>
                            <td><span class="badge text-bg-{{ ['draft'=>'secondary','scheduled'=>'info','published'=>'success','archived'=>'dark'][$a->status] }}">{{ ucfirst($a->status) }}</span></td>
                            <td class="small">{{ $aud }}</td>
                            <td class="small">{{ $a->requires_ack ? "$acks/$total" : '—' }}</td>
                            <td class="small text-muted">{{ $a->published_at?->diffForHumans() ?? ($a->scheduled_at? 'Scheduled '.$a->scheduled_at->diffForHumans() : '—') }}</td>
                            <td class="text-end">
                                @if ($a->status !== 'published')
                                    <form method="POST" action="{{ route('admin.document-board.publish',$a) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Publish</button></form>
                                @else
                                    <a href="{{ route('admin.document-board.receipts',$a) }}" class="btn btn-sm btn-outline-primary">Receipts</a>
                                    <form method="POST" action="{{ route('admin.document-board.remind',$a) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning">Remind</button></form>
                                @endif
                                @if ($a->status !== 'archived')
                                    <form method="POST" action="{{ route('admin.document-board.archive',$a) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Archive</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No announcements yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $announcements->links() }}</div>
    </div>

    {{-- Composer drawer --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="composer" style="width:560px">
        <div class="offcanvas-header"><h5 class="mb-0">New Announcement</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('admin.document-board.store') }}" enctype="multipart/form-data">@csrf
                <div class="mb-3"><label class="form-label small">Title</label><input name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small">Short summary</label><input name="summary" class="form-control" maxlength="300"></div>
                <div class="mb-3"><label class="form-label small">Full content</label><textarea name="body" class="form-control" rows="5"></textarea></div>
                <div class="row g-2 mb-3">
                    <div class="col"><label class="form-label small">Category</label>
                        <select name="category" class="form-select">@foreach ($categories as $c)<option>{{ $c }}</option>@endforeach</select></div>
                    <div class="col"><label class="form-label small">Priority</label>
                        <select name="priority" class="form-select"><option value="normal">Normal</option><option value="important">Important</option><option value="high">High</option><option value="critical">Critical</option></select></div>
                </div>
                <div class="mb-3"><label class="form-label small">Attachments (uploaded files)</label><input type="file" name="files[]" class="form-control" multiple></div>
                <div class="mb-3"><label class="form-label small">Or attach existing document IDs</label><input name="document_ids_str" class="form-control" placeholder="e.g. 12,15" oninput="this.name=''; this.insertAdjacentHTML('afterend', this.value.split(',').map(i=>`<input type=hidden name=document_ids[] value=${i.trim()}>`).join(''))"></div>
                <div class="border rounded p-3 mb-3">
                    <label class="form-label small fw-semibold">Target audience</label>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="target_all" value="1" id="tall"><label class="form-check-label" for="tall">All employees</label></div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label small">Roles</label>
                            @foreach (['ADMIN'=>'Admin','RECEPTIONIST'=>'Receptionists','CLINIC_STAFF'=>'Clinic Staff'] as $val=>$lbl)
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="roles[]" value="{{ $val }}"><label class="form-check-label small">{{ $lbl }}</label></div>
                            @endforeach
                        </div>
                        <div class="col-md-6"><label class="form-label small">Departments</label>
                            <select name="departments[]" class="form-select form-select-sm" multiple size="4">@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                            <label class="form-label small mt-2">Branches</label>
                            <select name="branches[]" class="form-select form-select-sm" multiple size="3">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
                        </div>
                    </div>
                    <label class="form-label small mt-2">Specific employees</label>
                    <select name="users[]" class="form-select form-select-sm" multiple size="4">@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>@endforeach</select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col"><label class="form-label small">Schedule publication</label><input type="datetime-local" name="scheduled_at" class="form-control"></div>
                    <div class="col"><label class="form-label small">Expires</label><input type="datetime-local" name="expires_at" class="form-control"></div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="pinned" value="1"> Pin to top</label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="requires_ack" value="1"> Require acknowledgment</label>
                </div>
                <button class="btn btn-primary w-100">Save Announcement</button>
                <p class="small text-muted mt-2 mb-0">Saves as draft (or scheduled). Use Publish to deliver to the audience.</p>
            </form>
        </div>
    </div>
</x-app-layout>
