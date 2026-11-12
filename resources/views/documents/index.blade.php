<x-app-layout>
    <x-slot name="header">{{ __('Office Documents') }}</x-slot>
    <x-slot name="headerActions">
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('docs.upload'))
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#uploadDrawer"><i class="hgi-stroke hgi-upload-01 me-1"></i>Upload</button>
        @endif
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('docs.folders'))
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#folderDrawer"><i class="hgi-stroke hgi-folder-01 me-1"></i>New Folder</button>
        @endif
        <a href="{{ route('documents.board') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-notification-01 me-1"></i>Company Updates</a>
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('board.publish') || auth()->user()->hasPermission('board.manage'))
        <a href="{{ route('admin.document-board.index') }}" class="btn btn-outline-secondary btn-sm">Board Admin</a>
        @endif
    </x-slot>

    @if (session('link_url'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span>Secure link: <code>{{ session('link_url') }}</code></span>
            <button class="btn btn-sm btn-outline-dark" onclick="navigator.clipboard.writeText('{{ session('link_url') }}')">Copy</button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Left navigation --}}
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('documents.index') }}" class="mb-2">
                        <input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Search files and folders…">
                    </form>
                    <div class="list-group list-group-flush small">
                        @foreach ([['my','My Files','folder-01'],['shared','Shared With Me','share-07'],['shared_by','Shared By Me','sent'],['starred','Starred','star'],['recent','Recent','clock-01'],['trash','Trash','delete-02']] as [$v,$label,$icon])
                            <a href="{{ route('documents.index',['view'=>$v]) }}" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center gap-2 {{ $view===$v?'active':'' }}">
                                <i class="hgi-stroke hgi-{{ $icon }}"></i>{{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold small">Folders</div>
                <div class="card-body p-2" style="max-height:340px;overflow:auto">
                    <div class="list-group list-group-flush small">
                        @forelse ($folders as $f)
                            <a href="{{ route('documents.index',['folder'=>$f->id]) }}" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center gap-2 {{ $folder?->id===$f->id?'active':'' }}">
                                <i class="hgi-stroke hgi-folder-02"></i>{{ $f->name }}
                                @if ($f->department)<span class="badge text-bg-light ms-auto">{{ $f->department->name }}</span>@endif
                            </a>
                        @empty
                            <div class="text-muted small px-2 py-1">No folders yet</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Main area --}}
        <div class="col-lg-9">
            @if ($breadcrumbs->count())
                <nav class="mb-2"><ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Documents</a></li>
                    @foreach ($breadcrumbs as $b)
                        <li class="breadcrumb-item {{ $loop->last?'active':'' }}">{{ $b->name }}</li>
                    @endforeach
                </ol></nav>
            @elseif ($view === 'search')
                <p class="small text-muted">Search results for “{{ $q }}”</p>
            @endif

            @if ($subfolders->count())
                <div class="row g-2 mb-3">
                    @foreach ($subfolders as $sf)
                        <div class="col-md-4">
                            <a href="{{ route('documents.index',['folder'=>$sf->id]) }}" class="card border-0 shadow-sm text-decoration-none text-dark">
                                <div class="card-body py-2 d-flex align-items-center gap-2">
                                    <i class="hgi-stroke hgi-folder-02 text-primary"></i>
                                    <span class="small fw-semibold">{{ $sf->name }}</span>
                                    <span class="ms-auto small text-muted">{{ $sf->documents->count() }}</span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Reference</th><th>Owner</th><th>Version</th><th>Size</th><th>Updated</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($docs as $doc)
                                <tr>
                                    <td>
                                        <button class="btn btn-link btn-sm p-0 text-dark text-decoration-none doc-details" data-id="{{ $doc->id }}">
                                            <i class="hgi-stroke hgi-file-01 me-1 text-muted"></i>{{ $doc->name }}
                                        </button>
                                        @if ($doc->classification !== 'internal')<span class="badge text-bg-{{ $doc->classification==='restricted'?'danger':'warning' }} ms-1">{{ ucfirst($doc->classification) }}</span>@endif
                                    </td>
                                    <td class="small text-muted">{{ $doc->reference }}</td>
                                    <td class="small">{{ $doc->owner->name }}</td>
                                    <td class="small">v{{ $doc->current_version }}</td>
                                    <td class="small text-muted">{{ $doc->current ? number_format($doc->current->size/1024,1).' KB' : '—' }}</td>
                                    <td class="small text-muted">{{ $doc->updated_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('documents.star',$doc) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-link {{ $doc->starredBy->where('user_id',auth()->id())->count()?'text-warning':'text-muted' }}" title="Star"><i class="hgi-stroke hgi-star"></i></button>
                                        </form>
                                        <a class="btn btn-sm btn-link text-muted" href="{{ route('documents.download',$doc) }}" title="Download"><i class="hgi-stroke hgi-download-01"></i></a>
                                        <button class="btn btn-sm btn-link text-muted doc-details" data-id="{{ $doc->id }}" title="Details"><i class="hgi-stroke hgi-more-vertical"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No documents</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($docs instanceof \Illuminate\Pagination\AbstractPaginator)<div class="card-footer bg-white">{{ $docs->links() }}</div>@endif
            </div>
        </div>
    </div>

    {{-- Upload drawer --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="uploadDrawer" style="width:460px">
        <div class="offcanvas-header"><h5 class="mb-0">Upload Files</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('documents.upload') }}" enctype="multipart/form-data">@csrf
                <div class="mb-3"><label class="form-label small">Files (multiple allowed)</label>
                    <input type="file" name="files[]" class="form-control" multiple required>
                    <div class="form-text">Allowed: {{ \App\Support\Settings::get('doc_allowed_mimes','pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,zip') }} · Max {{ number_format(\App\Support\Settings::get('doc_max_size_kb',10240)/1024) }}MB each</div>
                </div>
                <div class="mb-3"><label class="form-label small">Folder</label>
                    <select name="folder_id" class="form-select"><option value="">— My Files (no folder) —</option>
                        @foreach (\App\Services\DocumentService::visibleFolders(auth()->user()) as $f)<option value="{{ $f->id }}" {{ $folder?->id===$f->id?'selected':'' }}>{{ $f->ancestors()->pluck('name')->implode(' / ') }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="mb-3"><label class="form-label small">Classification</label>
                    <select name="classification" class="form-select"><option value="internal">Internal</option><option value="confidential">Confidential</option><option value="restricted">Restricted (no external links)</option></select>
                </div>
                <button class="btn btn-primary w-100">Upload</button>
            </form>
        </div>
    </div>

    {{-- New folder drawer --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="folderDrawer" style="width:440px">
        <div class="offcanvas-header"><h5 class="mb-0">New Folder</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('documents.folders.store') }}">@csrf
                <div class="mb-3"><label class="form-label small">Folder name</label><input name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small">Parent folder</label>
                    <select name="parent_id" class="form-select"><option value="">— Root —</option>
                        @foreach (\App\Services\DocumentService::visibleFolders(auth()->user()) as $f)<option value="{{ $f->id }}" {{ $folder?->id===$f->id?'selected':'' }}>{{ $f->ancestors()->pluck('name')->implode(' / ') }}</option>@endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col"><label class="form-label small">Department</label>
                        <select name="department_id" class="form-select"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                    <div class="col"><label class="form-label small">Branch</label>
                        <select name="branch_id" class="form-select"><option value="">—</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                </div>
                <div class="mb-3"><label class="form-label small">Visibility</label>
                    <select name="visibility" class="form-select">
                        <option value="private">Private (permissions only)</option>
                        <option value="department">Department members</option>
                        <option value="branch">Branch members</option>
                        <option value="org">Whole organization</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small">Category</label><input name="category" class="form-control" placeholder="e.g. Policies, Reports"></div>
                <div class="mb-3"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <button class="btn btn-primary w-100">Create Folder</button>
            </form>
        </div>
    </div>

    {{-- Document details drawer --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="docDrawer" style="width:520px">
        <div class="offcanvas-header"><h5 class="mb-0" id="dd-name">Document</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body" id="dd-body"><div class="text-muted small">Loading…</div></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const drawer = new bootstrap.Offcanvas('#docDrawer');
        const fmt = kb => kb > 1024 ? (kb/1024).toFixed(1)+' MB' : kb.toFixed(0)+' KB';
        document.querySelectorAll('.doc-details').forEach(b => b.addEventListener('click', async () => {
            const r = await fetch(`{{ url('documents') }}/${b.dataset.id}`, {headers:{'Accept':'application/json'}});
            if (!r.ok) return;
            const d = await r.json();
            document.getElementById('dd-name').textContent = d.name;
            const caps = d.caps;
            let html = `
                <div class="small text-muted mb-3">${d.reference} · ${d.owner}${d.folder ? ' · '+d.folder : ''} · v${d.version} · ${d.size?fmt(d.size/1024):''} · updated ${d.updated}</div>`;
            if (d.is_previewable && (caps.includes('preview')||caps.includes('download')))
                html += `<iframe src="{{ url('documents') }}/${d.id}/preview" class="w-100 border rounded mb-3" style="height:260px"></iframe>`;
            if (d.description) html += `<p class="small">${d.description}</p>`;
            html += `<div class="d-flex gap-2 mb-3 flex-wrap">`;
            if (caps.includes('download')) html += `<a class="btn btn-sm btn-primary" href="{{ url('documents') }}/${d.id}/download"><i class="hgi-stroke hgi-download-01"></i> Download</a>`;
            html += `</div>`;
            html += `<h6 class="small fw-semibold">Version history</h6><ul class="list-group list-group-flush small mb-3">` +
                d.versions.map(v => `<li class="list-group-item px-0 d-flex justify-content-between"><span>v${v.version} — ${v.by} · ${v.at}${v.note?' · '+v.note:''}</span>${caps.includes('versions')||v.version===d.version?`<a href="{{ url('documents') }}/${d.id}/download/${v.version}" class="btn btn-sm btn-link p-0">v${v.version}</a>`:''}</li>`).join('') + `</ul>`;
            if (caps.includes('upload'))
                html += `<form method="POST" action="{{ url('documents') }}/${d.id}/version" enctype="multipart/form-data" class="border-top pt-3 mb-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <label class="form-label small">Upload new version</label>
                    <div class="input-group input-group-sm"><input type="file" name="file" class="form-control" required><input name="note" class="form-control" placeholder="Change note"><button class="btn btn-outline-primary">Upload</button></div></form>`;
            if (caps.includes('edit'))
                html += `<form method="POST" action="{{ url('documents') }}/${d.id}" class="mb-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH">
                    <label class="form-label small">Rename</label>
                    <div class="input-group input-group-sm"><input name="name" class="form-control" value="${d.name}"><button class="btn btn-outline-secondary">Save</button></div></form>`;
            if (caps.includes('share'))
                html += `<form method="POST" action="{{ url('documents') }}/${d.id}/share" class="border-top pt-3 mb-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <h6 class="small fw-semibold">Share</h6>
                    <div class="row g-2 mb-2"><div class="col-5"><select name="grantee_type" class="form-select form-select-sm"><option value="user">Employee</option><option value="department">Department</option><option value="branch">Branch</option></select></div>
                    <div class="col-7"><input name="grantee_id" class="form-control form-control-sm" placeholder="ID (user/department/branch)" required></div></div>
                    <div class="d-flex flex-wrap gap-2 mb-2 small">
                        ${['view','preview','download','edit','share'].map(c=>`<label class="form-check"><input class="form-check-input" type="checkbox" name="caps[]" value="${c}" ${c==='view'?'checked':''}> ${c}</label>`).join('')}
                    </div>
                    <div class="row g-2"><div class="col"><input type="datetime-local" name="expires_at" class="form-control form-control-sm"></div>
                    <div class="col"><input name="reason" class="form-control form-control-sm" placeholder="Reason (optional)"></div>
                    <div class="col-auto"><button class="btn btn-sm btn-primary">Share</button></div></div></form>`;
            @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('docs.external'))
            if (caps.includes('share')) html += `<form method="POST" action="{{ url('documents') }}/${d.id}/link" class="mb-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <h6 class="small fw-semibold">External secure link</h6>
                    <div class="row g-2"><div class="col"><select name="mode" class="form-select form-select-sm"><option value="download">Download</option><option value="preview">Preview only</option></select></div>
                    <div class="col"><input type="datetime-local" name="expires_at" class="form-control form-control-sm" title="Expiry"></div>
                    <div class="col"><input name="password" class="form-control form-control-sm" placeholder="Password (opt.)"></div>
                    <div class="col"><input type="number" name="max_access" class="form-control form-control-sm" placeholder="Max uses" min="1"></div>
                    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Create</button></div></div></form>`;
            @endif
            @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('docs.perms'))
            html += `<form method="POST" action="{{ url('documents') }}/${d.id}/permission" class="border-top pt-3 mb-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="permissible_type" value="document"><input type="hidden" name="permissible_id" value="${d.id}">
                    <h6 class="small fw-semibold">Direct permission grant</h6>
                    <div class="row g-2 mb-2"><div class="col-5"><select name="grantee_type" class="form-select form-select-sm"><option value="user">Employee</option><option value="department">Department</option><option value="branch">Branch</option></select></div>
                    <div class="col-4"><input name="grantee_id" class="form-control form-control-sm" placeholder="ID" required></div>
                    <div class="col-3"><label class="form-check small"><input class="form-check-input" type="checkbox" name="deny" value="1"> Deny</label></div></div>
                    <div class="d-flex flex-wrap gap-2 mb-2 small">
                        ${['view','preview','download','upload','edit','share','delete','versions'].map(c=>`<label class="form-check"><input class="form-check-input" type="checkbox" name="caps[]" value="${c}" ${c==='view'?'checked':''}> ${c}</label>`).join('')}
                    </div>
                    <button class="btn btn-sm btn-outline-primary">Save permission</button></form>`;
            @endif
            if (caps.includes('delete')) html += `<form method="POST" action="{{ url('documents') }}/${d.id}" onsubmit="return confirm('Move to trash?')">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-sm btn-outline-danger"><i class="hgi-stroke hgi-delete-02"></i> Move to trash</button></form>`;
            document.getElementById('dd-body').innerHTML = html;
            drawer.show();
        }));
    });
    </script>
</x-app-layout>
