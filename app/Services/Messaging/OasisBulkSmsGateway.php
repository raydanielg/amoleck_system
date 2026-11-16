<?php

namespace App\Services\Messaging;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * OASIS BulkSMS adapter (bulksms.oasistech.co.tz).
 * Bearer-auth JSON API: POST /v1/sms, GET /v1/balance, GET /v1/logs.
 * Docs: https://bulksms.oasistech.co.tz/docs
 */
class OasisBulkSmsGateway implements SmsGatewayInterface
{
    public function send(SmsGateway $gateway, string $phone, string $body, ?string $idempotencyKey = null): array
    {
        try {
            // Per-message key: retries of THIS message never send twice, but a new
            // message with identical content must send again (new Message = new key).
            $idem = $idempotencyKey ?: (string) Str::uuid();

            $response = Http::timeout($gateway->timeout_seconds ?: 30)
                ->acceptJson()
                ->withToken($gateway->api_key)
                ->withHeaders(['Idempotency-Key' => $idem])
                ->post(rtrim($gateway->base_url, '/').'/'.ltrim($gateway->endpoint ?: '/v1/sms', '/'), [
                    'to' => $phone,
                    'sender' => $gateway->sender_id,
                    'message' => $body,
                ]);

            $json = $response->json() ?? [];

            if ($response->successful()) {
                // 200: sent | 202: pending / scheduled / processing — both accepted, do not retry
                $status = in_array($json['status'] ?? 'sent', ['sent', 'delivered'], true) ? 'sent' : 'submitted';

                return [
                    'accepted' => true,
                    'provider_ref' => $json['provider_ref'] ?? $json['sms_response']['msg_id'] ?? ($json['sms_log_id'] ?? null),
                    'status' => $status,
                    'raw' => $json,
                    'error' => null,
                ];
            }

            return [
                'accepted' => false,
                'provider_ref' => $json['provider_ref'] ?? null,
                'status' => 'failed',
                'raw' => $json,
                'error' => $json['error'] ?? ('HTTP '.$response->status()),
            ];
        } catch (\Throwable $e) {
            return ['accepted' => false, 'provider_ref' => null, 'status' => 'failed', 'raw' => null, 'error' => $e->getMessage()];
        }
    }

    public function balance(SmsGateway $gateway): ?array
    {
        try {
            $response = Http::timeout($gateway->timeout_seconds ?: 15)
                ->acceptJson()
                ->withToken($gateway->api_key)
                ->get(rtrim($gateway->base_url, '/').'/v1/balance');

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json() ?? [];

            return [
                'balance' => $json['balance'] ?? $json['credits'] ?? $json['credit_balance'] ?? null,
                'raw' => $json,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Pull recent message logs from the provider — used to reconcile delivery
     * status when webhooks are unavailable. Requires scope messages:read.
     *
     * @return array<int, array{provider_ref: ?string, status: string, raw: array}>
     */
    public function recentLogs(SmsGateway $gateway, int $limit = 50): array
    {
        try {
            $response = Http::timeout($gateway->timeout_seconds ?: 15)
                ->acceptJson()
                ->withToken($gateway->api_key)
                ->get(rtrim($gateway->base_url, '/').'/v1/logs', ['type' => 'sms', 'limit' => $limit]);

            if (! $response->successful()) {
                return [];
            }

            $json = $response->json() ?? [];
            $rows = is_array($json['data'] ?? null) ? $json['data'] : (is_array($json['logs'] ?? null) ? $json['logs'] : (array_is_list($json) ? $json : []));

            $out = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $mapped = $this->mapDeliveryReport($row);
                if ($mapped) {
                    $out[] = $mapped;
                }
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    public function mapDeliveryReport(array $payload): ?array
    {
        // Webhook envelope: {id, type: "sms.delivered", data: {provider_ref, status, ...}}
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        $ref = $data['provider_ref']
            ?? $data['msg_id']
            ?? $data['sms_response']['msg_id']
            ?? $data['sms_log_id']
            ?? null;

        $byEvent = [
            'sms.delivered' => 'delivered',
            'sms.pending' => 'submitted',
            'sms.failed' => 'failed',
            'sms.expired' => 'failed',
        ];
        $byStatus = [
            'delivered' => 'delivered',
            'sent' => 'sent',
            'submitted' => 'submitted',
            'pending' => 'submitted',
            'failed' => 'failed',
            'rejected' => 'failed',
            'expired' => 'failed',
            'undelivered' => 'failed',
        ];

        $type = strtolower((string) ($payload['type'] ?? ''));
        $status = $byEvent[$type] ?? $byStatus[strtolower((string) ($data['status'] ?? ''))] ?? null;

        if (! $ref || ! $status) {
            return null;
        }

        return ['provider_ref' => (string) $ref, 'status' => $status, 'raw' => $payload];
    }
}
