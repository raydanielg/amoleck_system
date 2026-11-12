<x-app-layout>
    <x-slot name="header">{{ __('My Profile') }}</x-slot>

    <div class="row g-3" style="max-width: 1000px;">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Profile Photo</div>
                <div class="card-body text-center">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="profile-avatar-lg" id="avatarPreview">
                    @else
                        <div class="profile-avatar-lg profile-avatar-initials mx-auto" id="avatarPreview">{{ $user->initials() }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none">
                        <label for="avatarInput" class="btn btn-outline-primary btn-sm">
                            <i class="hgi-stroke hgi-upload-01 me-1"></i>{{ __('Choose Photo') }}
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm" id="avatarSaveBtn" disabled>{{ __('Upload') }}</button>
                        @error('avatar')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </form>

                    @if ($user->avatar_path)
                        <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link btn-sm text-danger p-0">{{ __('Remove photo') }}</button>
                        </form>
                    @endif
                    <div class="small text-muted mt-2">JPG, PNG or WEBP — max 2MB</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold">Profile Information</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Save</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold">Change Password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" required>
                                @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" required>
                                @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Password</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 border-danger">
                <div class="card-header bg-white fw-semibold text-danger">Delete Account</div>
                <div class="card-body">
                    <p class="small text-muted">Once deleted, your account cannot be recovered. Your historical records remain attributed to you.</p>
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header"><h5 class="modal-title">Delete Account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p>Enter your password to confirm permanent deletion.</p>
                        <input type="password" name="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Password" required>
                        @error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('avatarInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const preview = document.getElementById('avatarPreview');
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.className = 'profile-avatar-lg';
                img.id = 'avatarPreview';
                preview.replaceWith(img);
            };
            reader.readAsDataURL(file);
            document.getElementById('avatarSaveBtn').disabled = false;
        });
    </script>
</x-app-layout>
