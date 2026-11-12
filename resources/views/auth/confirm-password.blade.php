<x-guest-layout>
    <h1 class="auth-title">{{ __('Confirm Password') }}</h1>
    <p class="auth-sub">{{ __('This is a secure area. Please confirm your password to continue.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="auth-field mb-4">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <div class="input-group auth-input-group">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Enter your password') }}" required autocomplete="current-password">
                <button type="button" class="input-group-text pw-toggle" data-toggle-password="#password"
                        aria-label="{{ __('Show password') }}">
                    <i class="hgi-stroke hgi-view"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-auth w-100">
            {{ __('Confirm') }}
        </button>
    </form>
</x-guest-layout>
