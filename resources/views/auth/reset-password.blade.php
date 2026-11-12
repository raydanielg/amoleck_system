<x-guest-layout>
    <p class="login-box-msg">{{ __('Reset your password') }}</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="input-group mb-3">
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
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
                   placeholder="{{ __('Password') }}" required autocomplete="new-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror"
                   placeholder="{{ __('Confirm Password') }}" required autocomplete="new-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Reset Password') }}</button>
    </form>
</x-guest-layout>
