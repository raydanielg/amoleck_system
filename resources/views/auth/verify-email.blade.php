<x-guest-layout>
    <h1 class="auth-title">{{ __('Verify Your Email') }}</h1>
    <p class="auth-sub">{{ __('Thanks for signing up! Please verify your email address by clicking the link we just emailed to you.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 small">{{ __('A new verification link has been sent to your email address.') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-auth w-100 mb-2">{{ __('Resend Verification Email') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-auth w-100">{{ __('Log Out') }}</button>
    </form>
</x-guest-layout>
