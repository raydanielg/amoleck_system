<x-guest-layout>
    <p class="login-box-msg">{{ __('Sign in to start your session') }}</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="input-group mb-3">
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="{{ __('Email') }}" required autofocus autocomplete="username">
            <span class="input-group-text"><i class="hgi-stroke hgi-mail-01"></i></span>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="{{ __('Password') }}" required autocomplete="current-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row align-items-center">
            <div class="col-7">
                <div class="form-check">
                    <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
                    <label for="remember_me" class="form-check-label">{{ __('Remember me') }}</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-primary w-100">{{ __('Log in') }}</button>
            </div>
        </div>
    </form>

    <div class="mt-3 mb-1">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
        @endif
    </div>
</x-guest-layout>
