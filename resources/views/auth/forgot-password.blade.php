<x-guest-layout>
    <p class="login-box-msg">{{ __('Forgot your password? Enter your email and we will send you a reset link.') }}</p>

    @if (session('status'))
        <div class="alert alert-success py-2">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="input-group mb-3">
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="{{ __('Email') }}" required autofocus>
            <span class="input-group-text"><i class="hgi-stroke hgi-mail-01"></i></span>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Email Password Reset Link') }}</button>
    </form>

    <div class="mt-3">
        <a href="{{ route('login') }}">{{ __('Back to login') }}</a>
    </div>
</x-guest-layout>
