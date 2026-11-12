<x-app-layout>
    <x-slot name="header">{{ __('SMS Gateway Settings') }}</x-slot>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Provider Configuration</div>
                <form method="POST" action="{{ route('admin.communication.gateway.update') }}" class="card-body row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-4"><label class="form-label">Provider</label><input name="provider" class="form-control" value="{{ old('provider', $gateway->provider) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Label</label><input name="label" class="form-control" value="{{ old('label', $gateway->label) }}" placeholder="e.g. Beem, Twilio"></div>
                    <div class="col-md-4"><label class="form-label">Sender ID</label><input name="sender_id" class="form-control" value="{{ old('sender_id', $gateway->sender_id) }}" placeholder="AMOLECK"></div>
                    <div class="col-md-8"><label class="form-label">API Base URL</label><input type="url" name="base_url" class="form-control" value="{{ old('base_url', $gateway->base_url) }}" placeholder="https://api.provider.com"></div>
                    <div class="col-md-4"><label class="form-label">Endpoint</label><input name="endpoint" class="form-control" value="{{ old('endpoint', $gateway->endpoint) }}" placeholder="/v1/sms/send"></div>
                    <div class="col-md-6"><label class="form-label">API Key</label><input type="password" name="api_key" class="form-control" value="" placeholder="{{ $gateway->api_key ? '•••••••• (saved)' : 'Not set' }}" autocomplete="new-password"></div>
                    <div class="col-md-6"><label class="form-label">API Secret</label><input type="password" name="api_secret" class="form-control" value="" placeholder="{{ $gateway->api_secret ? '•••••••• (saved)' : 'Not set' }}" autocomplete="new-password"></div>
                    <div class="col-md-4"><label class="form-label">Username</label><input name="username" class="form-control" value="{{ old('username', $gateway->username) }}"></div>
                    <div class="col-md-4"><label class="form-label">Password / Token</label><input type="password" name="password" class="form-control" value="" placeholder="{{ $gateway->password ? '•••••••• (saved)' : 'Not set' }}" autocomplete="new-password"></div>
                    <div class="col-md-4"><label class="form-label">Country Code</label><input name="country_code" class="form-control" value="{{ old('country_code', $gateway->country_code ?: '+255') }}"></div>
                    <div class="col-md-4"><label class="form-label">Timeout (s)</label><input type="number" name="timeout_seconds" class="form-control" value="{{ old('timeout_seconds', $gateway->timeout_seconds ?: 15) }}" min="5" max="120"></div>
                    <div class="col-md-4"><label class="form-label">Cost / Segment</label><input type="number" step="0.0001" name="cost_per_segment" class="form-control" value="{{ old('cost_per_segment', $gateway->cost_per_segment) }}"></div>
                    <div class="col-md-4"><label class="form-label">Manual Balance</label><input type="number" step="0.01" name="manual_balance" class="form-control" value="{{ old('manual_balance', $gateway->manual_balance) }}"></div>
                    <div class="col-md-6"><label class="form-label">Webhook Secret</label><input name="webhook_secret" class="form-control" value="" placeholder="{{ $gateway->webhook_secret ? '•••••••• (saved)' : 'Not set' }}"><div class="form-text">Callback URL: {{ url('/webhooks/sms') }}/&lt;secret&gt;</div></div>
                    <div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" name="enabled" value="1" class="form-check-input" id="gwEnabled" @checked($gateway->enabled)><label class="form-check-label" for="gwEnabled">Gateway enabled</label></div></div>
                    <div class="col-12"><button class="btn btn-primary">Save Gateway Settings</button></div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Send Test SMS</div>
                <form method="POST" action="{{ route('admin.communication.gateway.test') }}" class="card-body">
                    @csrf
                    <label class="form-label">Phone Number</label><input name="phone" class="form-control mb-2" placeholder="0712 345 678" required>
                    <label class="form-label">Test Message</label><textarea name="message" class="form-control mb-3" rows="3" required>Test message from {{ \App\Support\Settings::get('clinic_name') }}</textarea>
                    <button class="btn btn-outline-primary w-100">Send Test</button>
                    <p class="small text-muted mt-2 mb-0">"Accepted" means the gateway received the request — delivery is confirmed separately via webhook reports.</p>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
