<x-guest-layout>
    <h1 class="auth-title">{{ __('Set New Password') }}</h1>
    <p class="auth-sub">{{ __('Choose a strong password to secure your account.') }}</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-field mb-3">
            <label for="email" class="form-label">{{ __('Email Address') }}</label>
            <div class="input-group auth-input-group">
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="you@example.com" required autofocus autocomplete="username">
                <span class="input-group-text"><i class="hgi-stroke hgi-mail-01"></i></span>
            </div>
            @error('email')
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
            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <div class="input-group auth-input-group">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       placeholder="{{ __('Repeat new password') }}" required autocomplete="new-password">
                <button type="button" class="input-group-text pw-toggle" data-toggle-password="#password_confirmation"
                        aria-label="{{ __('Show password') }}">
                    <i class="hgi-stroke hgi-view"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-auth w-100">
            {{ __('Reset Password') }}
        </button>
    </form>
</x-guest-layout>
