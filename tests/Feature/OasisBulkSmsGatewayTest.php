<?php

namespace Tests\Feature;

use App\Models\CommunicationConsent;
use App\Models\SmsGateway;
use App\Services\Messaging\OasisBulkSmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OasisBulkSmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): SmsGateway
    {
        return new SmsGateway([
            'provider' => 'oasis',
            'base_url' => 'https://bulksms.oasistech.co.tz',
            'endpoint' => '/v1/sms',
            'api_key' => 'test-api-key',
            'sender_id' => 'AMOLECK',
            'timeout_seconds' => 15,
            'enabled' => true,
        ]);
    }

    public function test_send_submits_correct_payload_and_auth(): void
    {
        Http::fake([
            'bulksms.oasistech.co.tz/*' => Http::response([
                'number' => '+255712345678',
                'status' => 'sent',
                'sms_log_id' => 123456,
                'provider_ref' => '1880000001',
                'credit_info' => ['credits_charged' => 1, 'balance_after' => 6],
            ], 200),
        ]);

        $result = (new OasisBulkSmsGateway)->send($this->gateway(), '+255712345678', 'Habari {name}', 'msg-key-1');

        $this->assertTrue($result['accepted']);
        $this->assertSame('sent', $result['status']);
        $this->assertSame('1880000001', $result['provider_ref']);
        $this->assertNull($result['error']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bulksms.oasistech.co.tz/v1/sms'
                && $request->hasHeader('Authorization', 'Bearer test-api-key')
                && $request->hasHeader('Idempotency-Key', 'msg-key-1')
                && $request['to'] === '+255712345678'
                && $request['sender'] === 'AMOLECK'
                && $request['message'] === 'Habari {name}';
        });
    }

    public function test_identical_content_sends_use_distinct_idempotency_keys(): void
    {
        Http::fake(['bulksms.oasistech.co.tz/*' => Http::response(['status' => 'sent', 'provider_ref' => '1'], 200)]);

        $adapter = new OasisBulkSmsGateway;
        $adapter->send($this->gateway(), '+255712345678', 'Same text');
        $adapter->send($this->gateway(), '+255712345678', 'Same text');

        $keys = collect(Http::recorded())->map(fn ($pair) => $pair[0]->header('Idempotency-Key')[0] ?? null);
        $this->assertCount(2, $keys->unique());
    }

    public function test_send_marks_provider_error_as_failed(): void
    {
        Http::fake([
            'bulksms.oasistech.co.tz/*' => Http::response([
                'error' => 'Insufficient credits',
                'credits_required' => 1,
                'credits_available' => 0,
            ], 402),
        ]);

        $result = (new OasisBulkSmsGateway)->send($this->gateway(), '+255712345678', 'Hi');

        $this->assertFalse($result['accepted']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('Insufficient credits', $result['error']);
    }

    public function test_send_treats_pending_acceptance_as_submitted(): void
    {
        Http::fake([
            'bulksms.oasistech.co.tz/*' => Http::response([
                'status' => 'pending',
                'sms_log_id' => 123458,
                'provider_ref' => null,
            ], 202),
        ]);

        $result = (new OasisBulkSmsGateway)->send($this->gateway(), '+255712345678', 'Hi');

        $this->assertTrue($result['accepted']);
        $this->assertSame('submitted', $result['status']);
    }

    public function test_balance_returns_credit_amount(): void
    {
        Http::fake([
            'bulksms.oasistech.co.tz/v1/balance' => Http::response(['balance' => 42], 200),
        ]);

        $result = (new OasisBulkSmsGateway)->balance($this->gateway());

        $this->assertSame(42, $result['balance']);
    }

    public function test_delivery_report_maps_status(): void
    {
        $adapter = new OasisBulkSmsGateway;

        $mapped = $adapter->mapDeliveryReport(['provider_ref' => '1880000001', 'status' => 'DELIVERED']);
        $this->assertSame('delivered', $mapped['status']);
        $this->assertSame('1880000001', $mapped['provider_ref']);

        $this->assertNull($adapter->mapDeliveryReport(['status' => 'delivered']));
        $this->assertNull($adapter->mapDeliveryReport(['provider_ref' => 'x', 'status' => 'unknown']));
    }

    public function test_delivery_report_maps_webhook_envelope(): void
    {
        $adapter = new OasisBulkSmsGateway;

        $mapped = $adapter->mapDeliveryReport([
            'id' => 'evt_123',
            'type' => 'sms.failed',
            'data' => ['provider_ref' => '1880000001', 'recipient' => '+255712345678', 'error' => 'refused'],
        ]);

        $this->assertSame('failed', $mapped['status']);
        $this->assertSame('1880000001', $mapped['provider_ref']);
    }

    public function test_webhook_endpoint_accepts_valid_hmac_signature(): void
    {
        SmsGateway::create([
            'provider' => 'oasis',
            'base_url' => 'https://bulksms.oasistech.co.tz',
            'endpoint' => '/v1/sms',
            'api_key' => 'k',
            'webhook_secret' => 'whsec',
        ]);

        $body = json_encode([
            'id' => 'evt_1',
            'type' => 'sms.delivered',
            'data' => ['provider_ref' => '1880000001', 'status' => 'sent'],
        ]);
        $signature = hash_hmac('sha256', $body, 'whsec');

        $this->call('POST', '/webhooks/sms/oasis', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_BULKSMS_SIGNATURE' => $signature,
        ], $body)->assertOk()->assertJson(['ok' => true]);

        $this->call('POST', '/webhooks/sms/oasis', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_BULKSMS_SIGNATURE' => 'bad-signature',
        ], $body)->assertForbidden();
    }

    public function test_webhook_marks_opted_out_recipient(): void
    {
        SmsGateway::create([
            'provider' => 'oasis',
            'base_url' => 'https://bulksms.oasistech.co.tz',
            'endpoint' => '/v1/sms',
            'api_key' => 'k',
            'webhook_secret' => 'whsec',
        ]);

        $body = json_encode([
            'type' => 'recipient.opted_out',
            'data' => ['recipient' => '+255712345678'],
        ]);

        $this->call('POST', '/webhooks/sms/oasis', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_BULKSMS_SIGNATURE' => hash_hmac('sha256', $body, 'whsec'),
        ], $body)->assertOk();

        $this->assertTrue(CommunicationConsent::optedOut('+255712345678'));
    }
}
