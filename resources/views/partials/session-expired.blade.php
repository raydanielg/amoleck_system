@auth
{{-- Session expiry warning + re-authentication modals --}}
<div class="modal fade session-modal" id="sessionWarnModal" tabindex="-1"
     aria-labelledby="sessionWarnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered session-modal-dialog">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="session-modal-icon warn">
                    <i class="hgi-stroke hgi-clock-01"></i>
                </div>
                <h5 class="session-modal-title" id="sessionWarnLabel">{{ __('Session Expiring Soon') }}</h5>
                <p class="session-modal-sub">
                    {{ __('Your session is about to expire.') }}
                    <span id="sessionWarnCountdown" class="fw-semibold"></span>
                </p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-auth" id="sessionContinueBtn">
                        {{ __('Continue Working') }}
                    </button>
                    <button type="button" class="btn btn-link session-signout-link" data-session-signout>
                        {{ __('Sign Out') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade session-modal" id="sessionExpiredModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-labelledby="sessionExpiredLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered session-modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-4">
                <div class="text-center">
                    <div class="session-modal-icon">
                        <i class="hgi-stroke hgi-square-lock-password"></i>
                    </div>
                    <h5 class="session-modal-title" id="sessionExpiredLabel">{{ __('Session Expired') }}</h5>
                    <p class="session-modal-sub">
                        {{ __('Please verify your identity to continue using :app.', ['app' => config('app.name')]) }}
                    </p>
                </div>

                <form id="sessionReauthForm" novalidate>
                    <div class="auth-field mb-3">
                        <label class="form-label" for="reauthEmail">{{ __('Account') }}</label>
                        <input id="reauthEmail" type="email" class="form-control" readonly
                               value="{{ Auth::user()->email }}">
                    </div>
                    <div class="auth-field mb-2">
                        <label class="form-label" for="reauthPassword">{{ __('Password') }}</label>
                        <div class="input-group auth-input-group">
                            <input id="reauthPassword" type="password" class="form-control"
                                   placeholder="{{ __('Enter your password') }}" autocomplete="current-password" required>
                            <button type="button" class="input-group-text pw-toggle" data-toggle-password="#reauthPassword"
                                    aria-label="{{ __('Show password') }}">
                                <i class="hgi-stroke hgi-view"></i>
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-danger py-2 small d-none" id="reauthError" role="alert"></div>
                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-auth" id="reauthSubmit">
                            {{ __('Continue Session') }}
                        </button>
                        <button type="button" class="btn btn-link session-signout-link" data-session-signout>
                            {{ __('Or sign out securely') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form id="sessionSignoutForm" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
@endauth
