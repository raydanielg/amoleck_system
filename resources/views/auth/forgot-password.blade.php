<x-guest-layout>
    <h1 class="auth-title">{{ __('Forgot Password') }}</h1>
    <p class="auth-sub">{{ __('Enter your email address and we will send you a secure link to reset your password.') }}</p>

    @if (session('status'))
        <div class="alert alert-success py-2 small">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="auth-field mb-4">
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

        <button type="submit" class="btn btn-primary btn-auth w-100">
            {{ __('Send Reset Link') }}
        </button>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="small text-decoration-none">
            <i class="hgi-stroke hgi-arrow-left-01 me-1"></i>{{ __('Back to sign in') }}
        </a>
    </div>
</x-guest-layout>
