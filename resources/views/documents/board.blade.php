<x-app-layout>
    <x-slot name="header">{{ __('Company Updates & Official Documents') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-folder-01 me-1"></i>Documents</a>
    </x-slot>

    @php $prBadge = ['normal'=>'secondary','important'=>'primary','high'=>'warning','critical'=>'danger']; @endphp
    <div class="row g-3">
        @forelse ($announcements as $a)
            @php $r = $receipts[$a->id] ?? null; @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card shadow-sm border-0 h-100 {{ $a->pinned ? 'border-start border-4 border-primary' : '' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge text-bg-{{ $prBadge[$a->priority] ?? 'secondary' }}">{{ ucfirst($a->priority) }}</span>
                            <span class="badge text-bg-light">{{ $a->category }}</span>
                            @if ($a->pinned)<i class="hgi-stroke hgi-pin text-primary" title="Pinned"></i>@endif
                        </div>
                        <h6 class="mb-1">{{ $a->title }}</h6>
                        <p class="small text-muted mb-2">{{ Str::limit($a->summary ?? $a->body, 110) }}</p>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="small text-muted">{{ $a->published_at?->format('d M Y') }} · {{ $a->publisher?->name }}</span>
                            <div class="d-flex align-items-center gap-2">
                                @if ($a->attachments->count())<i class="hgi-stroke hgi-attachment-01 text-muted" title="{{ $a->attachments->count() }} attachment(s)"></i>@endif
                                @if ($r?->status === 'acknowledged')<span class="badge text-bg-success">Acknowledged</span>
                                @elseif ($a->requires_ack)<span class="badge text-bg-warning">Ack required</span>
                                @elseif ($r?->status === 'viewed')<span class="badge text-bg-light">Viewed</span>
                                @else<span class="badge text-bg-light">New</span>@endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white"><a href="{{ route('documents.board.show',$a) }}" class="btn btn-sm btn-outline-primary w-100">Open</a></div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card shadow-sm border-0"><div class="card-body text-center text-muted py-5">No updates published for you yet.</div></div></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $announcements->links() }}</div>
</x-app-layout>
