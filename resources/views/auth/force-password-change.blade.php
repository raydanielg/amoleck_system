<x-guest-layout>
    <p class="login-box-msg">{{ __('Set a new password to continue.') }}</p>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')

        <div class="input-group mb-3">
            <input id="current_password" type="password" name="current_password"
                   class="form-control @error('current_password') is-invalid @enderror"
                   placeholder="{{ __('Current Password') }}" required autocomplete="current-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
            @error('current_password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="{{ __('New Password') }}" required autocomplete="new-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control"
                   placeholder="{{ __('Confirm New Password') }}" required autocomplete="new-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Change Password') }}</button>
    </form>
</x-guest-layout>
