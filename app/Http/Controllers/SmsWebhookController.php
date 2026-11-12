<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\SmsGateway;
use App\Services\Messaging\GatewayManager;
use Illuminate\Http\Request;

class SmsWebhookController extends Controller
{
    /** Delivery report callback — authenticated by webhook secret in URL. */
    public function handle(Request $request, string $secret)
    {
        $gateway = SmsGateway::current();
        abort_unless($gateway->webhook_secret && hash_equals($gateway->webhook_secret, $secret), 403);

        $mapped = GatewayManager::adapter($gateway)->mapDeliveryReport($request->all());
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
