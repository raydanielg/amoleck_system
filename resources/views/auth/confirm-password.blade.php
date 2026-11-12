<x-guest-layout>
    <p class="login-box-msg">{{ __('This is a secure area. Please confirm your password to continue.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="input-group mb-3">
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="{{ __('Password') }}" required autocomplete="current-password">
            <span class="input-group-text"><i class="hgi-stroke hgi-lock-password"></i></span>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Confirm') }}</button>
    </form>
</x-guest-layout>
