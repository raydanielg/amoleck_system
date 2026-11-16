<?php

namespace App\Http\Controllers;

use App\Models\CommunicationConsent;
use App\Models\Message;
use App\Models\SmsGateway;
use App\Services\Messaging\GatewayManager;
use Illuminate\Http\Request;

class SmsWebhookController extends Controller
{
    /** Delivery report callback — authenticated by webhook secret (URL token or HMAC signature). */
    public function handle(Request $request, string $secret)
    {
        $gateway = SmsGateway::current();
        abort_unless($gateway->webhook_secret, 403);

        if (in_array($gateway->provider, ['oasis', 'bulksms'], true)) {
            // BulkSMS signs with X-BulkSMS-Signature = HMAC-SHA256(raw body, webhook secret)
            $signature = (string) $request->header('X-BulkSMS-Signature', '');
            $expected = hash_hmac('sha256', $request->getContent(), $gateway->webhook_secret);
            abort_unless($signature !== '' && hash_equals($expected, $signature), 403);
        } else {
            abort_unless(hash_equals($gateway->webhook_secret, $secret), 403);
        }

        $payload = $request->all();

        // Recipients opting out via the provider stop all future SMS to that number
        if (($payload['type'] ?? '') === 'recipient.opted_out') {
            $phone = $payload['data']['recipient'] ?? $payload['data']['number'] ?? null;
            if ($phone) {
                CommunicationConsent::updateOrCreate(['phone' => $phone], ['sms_opt_out' => true]);
            }

            return response()->json(['ok' => true]);
        }

        $mapped = GatewayManager::adapter($gateway)->mapDeliveryReport($payload);
        if (! $mapped) {
            return response()->json(['ok' => false], 422);
        }

        $message = Message::where('provider_ref', $mapped['provider_ref'])->first();
        if ($message) {
            $message->setStatus($mapped['status'], 'Webhook delivery report');
        }

        return response()->json(['ok' => true]);
    }
}
