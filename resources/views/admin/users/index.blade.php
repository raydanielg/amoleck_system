<x-app-layout>
    <x-slot name="header">{{ __('Users') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Staff User</a>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Created</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge {{ $user->isAdmin() ? 'text-bg-dark' : 'text-bg-success' }}">{{ $user->role }}</span></td>
                            <td><span class="badge {{ $user->isActive() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($user->status) }}</span></td>
                            <td class="small">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Never' }}</td>
                            <td class="small">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="d-inline" onsubmit="return confirm('Reset password for {{ $user->name }}?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Reset PW</button>
                                </form>
                                @if ($user->id !== Auth::id())
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm {{ $user->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $user->isActive() ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No users.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())<div class="card-footer bg-white">{{ $users->links() }}</div>@endif
    </div>
</x-app-layout>
