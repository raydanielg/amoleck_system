<x-app-layout>
    <x-slot name="header">{{ $target->name }} — Effective Permissions</x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.access.index') }}" class="btn btn-sm btn-outline-secondary">Back to Staff &amp; Access</a></x-slot>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 small text-muted">
            Role: <strong>{{ $target->role }}</strong> · Status: <strong>{{ $target->status }}</strong>.
            The "Source" column explains why each permission is allowed or denied — this is the
            same decision the backend makes on every request.
        </div>
    </div>

    @foreach ($rows as $group => $perms)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-semibold">{{ $group }}</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th style="width:220px">Permission</th><th>Description</th><th style="width:110px">Decision</th><th style="width:220px">Source</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($perms as $perm)
                            <tr>
                                <td><code>{{ $perm['key'] }}</code></td>
                                <td class="small text-muted">{{ $perm['description'] }}</td>
                                <td>
                                    <span class="badge {{ $perm['allowed'] ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $perm['allowed'] ? 'Allowed' : 'Denied' }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $perm['via'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</x-app-layout>
