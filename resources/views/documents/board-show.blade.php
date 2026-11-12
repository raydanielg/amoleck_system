<x-app-layout>
    <x-slot name="header">{{ $announcement->title }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('documents.board') }}" class="btn btn-outline-secondary btn-sm">Back to board</a></x-slot>
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge text-bg-primary">{{ $announcement->category }}</span>
                    <span class="badge text-bg-{{ ['normal'=>'secondary','important'=>'primary','high'=>'warning','critical'=>'danger'][$announcement->priority] }}">{{ ucfirst($announcement->priority) }}</span>
                    @if ($announcement->pinned)<span class="badge text-bg-light">Pinned</span>@endif
                </div>
                @if ($announcement->summary)<p class="lead fs-6">{{ $announcement->summary }}</p>@endif
                <div class="border-top pt-3" style="white-space:pre-wrap">{{ $announcement->body }}</div>

                @if ($announcement->attachments->count())
                    <h6 class="mt-4 small fw-semibold">Attachments</h6>
                    <div class="list-group list-group-flush">
                        @foreach ($announcement->attachments as $doc)
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span><i class="hgi-stroke hgi-file-01 me-1 text-muted"></i>{{ $doc->name }} <span class="text-muted small">v{{ $doc->current_version }}</span></span>
                                <span>
                                    <a href="{{ route('documents.preview',$doc) }}" class="btn btn-sm btn-outline-secondary">Preview</a>
                                    <a href="{{ route('documents.download',$doc) }}" class="btn btn-sm btn-outline-primary">Download</a>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="border-top mt-4 pt-3 d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Published {{ $announcement->published_at?->toDayDateTimeString() }} by {{ $announcement->publisher?->name }}</span>
                    @if ($announcement->requires_ack && $receipt->status !== 'acknowledged')
                        <form method="POST" action="{{ route('documents.board.ack',$announcement) }}" class="d-flex gap-2">@csrf
                            <input name="comment" class="form-control form-control-sm" placeholder="Comment (optional)">
                            <button class="btn btn-sm btn-success">I acknowledge receipt</button>
                        </form>
                    @elseif ($receipt->status === 'acknowledged')
                        <span class="badge text-bg-success">Acknowledged {{ $receipt->acknowledged_at->toDayDateTimeString() }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div></div>
</x-app-layout>
