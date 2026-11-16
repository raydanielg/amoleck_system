<?php

namespace Tests\Feature;

use App\Models\SmsGateway;
use App\Services\Messaging\OasisBulkSmsGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OasisBulkSmsGatewayTest extends TestCase
{
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

        $result = (new OasisBulkSmsGateway)->send($this->gateway(), '+255712345678', 'Habari {name}');

        $this->assertTrue($result['accepted']);
        $this->assertSame('sent', $result['status']);
        $this->assertSame('1880000001', $result['provider_ref']);
        $this->assertNull($result['error']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bulksms.oasistech.co.tz/v1/sms'
                && $request->hasHeader('Authorization', 'Bearer test-api-key')
                && $request->hasHeader('Idempotency-Key')
                && $request['to'] === '+255712345678'
                && $request['sender'] === 'AMOLECK'
                && $request['message'] === 'Habari {name}';
        });
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
}
