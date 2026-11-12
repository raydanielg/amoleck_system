<?php

namespace App\Services\Messaging;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Illuminate\Support\Facades\Http;

/**
 * Generic JSON HTTP adapter — posts {to, from, message} with the configured
 * credentials. Works for many JSON SMS APIs without code changes.
 */
class GenericHttpGateway implements SmsGatewayInterface
{
    public function send(SmsGateway $gateway, string $phone, string $body): array
    {
        try {
            $response = Http::timeout($gateway->timeout_seconds ?: 15)
                ->acceptJson()
                ->withHeaders(array_filter([
                    'Authorization' => $gateway->api_key ? 'Bearer '.$gateway->api_key : null,
                    'X-API-Key' => $gateway->api_key,
                ]))
                ->post(rtrim($gateway->base_url, '/').'/'.ltrim($gateway->endpoint, '/'), [
                    'to' => $phone,
                    'from' => $gateway->sender_id,
                    'message' => $body,
                ]);

            $json = $response->json();

            return [
                'accepted' => $response->successful(),
                'provider_ref' => $json['message_id'] ?? $json['id'] ?? $json['reference'] ?? null,
                'status' => $response->successful() ? 'submitted' : 'failed',
                'raw' => $json ?? $response->body(),
                'error' => $response->successful() ? null : ('HTTP '.$response->status()),
            ];
        } catch (\Throwable $e) {
            return ['accepted' => false, 'provider_ref' => null, 'status' => 'failed', 'raw' => null, 'error' => $e->getMessage()];
        }
    }

    public function balance(SmsGateway $gateway): ?array
    {
        return null; // generic adapter does not support balance lookups
    }

    public function mapDeliveryReport(array $payload): ?array
    {
        $ref = $payload['message_id'] ?? $payload['id'] ?? $payload['reference'] ?? null;
        $status = strtolower((string) ($payload['status'] ?? ''));
        $map = ['delivered' => 'delivered', 'sent' => 'sent', 'failed' => 'failed', 'rejected' => 'failed', 'expired' => 'failed', 'submitted' => 'submitted'];
        if (! $ref || ! isset($map[$status])) {
            return null;
        }

        return ['provider_ref' => $ref, 'status' => $map[$status], 'raw' => $payload];
    }
}
