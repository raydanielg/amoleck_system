<x-app-layout>
    <x-slot name="header">Edit User — {{ $user->name }}</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 600px;">
        <div class="card-body">
            <dl class="row small mb-3">
                <dt class="col-4 text-muted">Role</dt><dd class="col-8">{{ $user->role }}</dd>
                <dt class="col-4 text-muted">Status</dt><dd class="col-8">{{ ucfirst($user->status) }}</dd>
                <dt class="col-4 text-muted">Last Login</dt><dd class="col-8">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Never' }}</dd>
            </dl>
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
