<x-app-layout>
    <x-slot name="header">{{ __('Notifications') }}</x-slot>
    <x-slot name="headerActions">
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">Mark all as read</button>
        </form>
    </x-slot>

    <div class="card shadow-sm border-0">
        <ul class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <li class="list-group-item d-flex justify-content-between align-items-start {{ $notification->isRead() ? '' : 'bg-light' }}">
                    <div>
                        <div class="{{ $notification->isRead() ? '' : 'fw-semibold' }}">{{ $notification->title }}</div>
                        <div class="small text-muted">{{ $notification->message }}</div>
                        <div class="small text-muted mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                    @if ($notification->url)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm">Open</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-5">No notifications.</li>
            @endforelse
        </ul>
        @if ($notifications->hasPages())<div class="card-footer bg-white">{{ $notifications->links() }}</div>@endif
    </div>
</x-app-layout>
