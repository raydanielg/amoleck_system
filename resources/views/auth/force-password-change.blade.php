<x-guest-layout>
    <h1 class="auth-title">{{ __('Change Password') }}</h1>
    <p class="auth-sub">{{ __('For your security, set a new password before continuing.') }}</p>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')

        <div class="auth-field mb-3">
            <label for="current_password" class="form-label">{{ __('Current Password') }}</label>
            <div class="input-group auth-input-group">
                <input id="current_password" type="password" name="current_password"
                       class="form-control @error('current_password') is-invalid @enderror"
                       placeholder="{{ __('Enter current password') }}" required autocomplete="current-password">
                <button type="button" class="input-group-text pw-toggle" data-toggle-password="#current_password"
                        aria-label="{{ __('Show password') }}">
                    <i class="hgi-stroke hgi-view"></i>
                </button>
            </div>
            @error('current_password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="auth-field mb-3">
            <label for="password" class="form-label">{{ __('New Password') }}</label>
            <div class="input-group auth-input-group">
                <input id="password" type="password" name="password" data-strength="#pwMeter"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Enter new password') }}" required autocomplete="new-password">
                <button type="button" class="input-group-text pw-toggle" data-toggle-password="#password"
                        aria-label="{{ __('Show password') }}">
                    <i class="hgi-stroke hgi-view"></i>
                </button>
            </div>
            <div class="pw-meter" id="pwMeter"><span></span></div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="auth-field mb-4">
            <label for="password_confirmation" class="form-label">{{ __('Confirm New Password') }}</label>
            <div class="input-group auth-input-group">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-control"
                       placeholder="{{ __('Repeat new password') }}" required autocomplete="new-password">
                <button type="button" class="input-group-text pw-toggle" data-toggle-password="#password_confirmation"
                        aria-label="{{ __('Show password') }}">
                    <i class="hgi-stroke hgi-view"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-auth w-100">
            {{ __('Change Password') }}
        </button>
    </form>
</x-guest-layout>
