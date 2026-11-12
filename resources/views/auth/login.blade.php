<x-guest-layout>
    <h1 class="auth-title">{{ __('Welcome Back') }}</h1>
    <p class="auth-sub">{{ __('Sign in to access :app.', ['app' => config('app.name')]) }}</p>

    @if (session('status'))
        <div class="alert alert-success py-2 small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="auth-field mb-3">
            <label for="email" class="form-label">{{ __('Email Address') }}</label>
            <div class="input-group auth-input-group">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="you@example.com" required autofocus autocomplete="username">
                <span class="input-group-text"><i class="hgi-stroke hgi-mail-01"></i></span>
            </div>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="auth-field mb-3">
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
                <label for="remember_me" class="form-check-label small">{{ __('Remember me') }}</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small text-decoration-none">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-auth w-100">
            {{ __('Sign In') }}
        </button>
    </form>
</x-guest-layout>
