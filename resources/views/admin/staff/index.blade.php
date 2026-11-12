<x-app-layout>
    <x-slot name="header">{{ __('Staff Management') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">User Accounts</a>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Staff</th><th>Status</th><th>Joined</th><th>Last Login</th><th class="text-center">Patients</th><th class="text-center">Visits</th><th class="text-center">Expenses</th><th></th></tr></thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $member->name }}</div>
                                <div class="small text-muted">{{ $member->email }}</div>
                            </td>
                            <td><span class="badge-status {{ $member->status === 'active' ? 'status-paid' : 'status-voided' }}">{{ $member->status }}</span></td>
                            <td>{{ $member->created_at->format('d/m/Y') }}</td>
                            <td>{{ $member->last_login_at?->format('d/m/Y H:i') ?? 'Never' }}</td>
                            <td class="text-center">{{ $member->patients_count }}</td>
                            <td class="text-center">{{ $member->visits_count }}</td>
                            <td class="text-center">{{ $member->expenses_count }}</td>
                            <td><a href="{{ route('admin.staff.show', $member) }}" class="btn btn-outline-primary btn-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No staff accounts. <a href="{{ route('admin.users.create') }}">Create one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($staff->hasPages())<div class="card-footer bg-white">{{ $staff->links() }}</div>@endif
    </div>
</x-app-layout>
