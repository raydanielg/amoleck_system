<x-guest-layout>
    <p class="login-box-msg">{{ __('Thanks for signing up! Please verify your email address by clicking the link we just emailed to you.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2">{{ __('A new verification link has been sent to your email address.') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary w-100 mb-2">{{ __('Resend Verification Email') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary w-100">{{ __('Log Out') }}</button>
    </form>
</x-guest-layout>
