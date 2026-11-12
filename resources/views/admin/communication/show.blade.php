<x-app-layout>
    <x-slot name="header">{{ __('Message '.$message->message_reference) }}</x-slot>
    <x-slot name="headerActions">
        @if ($message->status === 'failed' && $message->attempts < 3)
            <form method="POST" action="{{ route('admin.communication.messages.retry', $message) }}">@csrf<button class="btn btn-outline-primary btn-sm">Retry</button></form>
        @endif
        <a href="{{ route('admin.communication.messages') }}" class="btn btn-outline-secondary btn-sm">Logs</a>
    </x-slot>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0"><div class="card-body">
                <p class="border rounded p-3 bg-light">{{ $message->body }}</p>
                <dl class="row small mb-0">
                    <dt class="col-4 text-muted">Recipient</dt><dd class="col-8">{{ $message->recipient_name ?? '—' }} · {{ $message->phone }}</dd>
                    <dt class="col-4 text-muted">Type</dt><dd class="col-8">{{ $message->type }}{{ $message->event_key ? ' · '.$message->event_key : '' }}</dd>
                    <dt class="col-4 text-muted">Template</dt><dd class="col-8">{{ $message->template?->name ?? '—' }}</dd>
                    <dt class="col-4 text-muted">Segments / Cost</dt><dd class="col-8">{{ $message->segments }} / {{ $message->cost ? number_format((float) $message->cost, 4) : '—' }}</dd>
                    <dt class="col-4 text-muted">Provider Ref</dt><dd class="col-8">{{ $message->provider_ref ?? '—' }}</dd>
                    <dt class="col-4 text-muted">Attempts</dt><dd class="col-8">{{ $message->attempts }}</dd>
                    @if ($message->failure_reason)<dt class="col-4 text-muted">Failure</dt><dd class="col-8 text-danger">{{ $message->failure_reason }}</dd>@endif
                </dl>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Delivery Events</div>
                <ul class="list-group list-group-flush">
                    @forelse ($message->events as $ev)
                        <li class="list-group-item small"><span class="badge text-bg-light border">{{ $ev->status }}</span> {{ $ev->detail }}<div class="text-muted">{{ $ev->created_at->format('d M Y H:i:s') }}</div></li>
                    @empty
                        <li class="list-group-item text-muted">No events.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
