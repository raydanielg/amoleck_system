<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\NotificationRule;
use App\Models\SmsGateway;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    private function gateway(): SmsGateway
    {
        return SmsGateway::create([
            'provider' => 'generic', 'base_url' => 'https://api.test', 'endpoint' => '/sms',
            'api_key' => 'secret-key', 'sender_id' => 'AMOLECK', 'enabled' => true,
            'country_code' => '+255', 'cost_per_segment' => 25,
        ]);
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('+255712345678', MessagingService::normalizePhone('0712345678', '+255'));
        $this->assertSame('+255712345678', MessagingService::normalizePhone('255712345678', '+255'));
        $this->assertSame('+255712345678', MessagingService::normalizePhone('+255 712 345 678', '+255'));
        $this->assertNull(MessagingService::normalizePhone('abc', '+255'));
    }

    public function test_message_submission_marks_submitted_not_delivered(): void
    {
        $this->gateway();
        Http::fake(['api.test/*' => Http::response(['message_id' => 'prov-1'], 200)]);

        $m = MessagingService::sendNow(['phone' => '0712345678', 'body' => 'Hello test', 'type' => 'test']);
        $this->assertSame('submitted', $m->status); // accepted, NOT delivered
        $this->assertSame('prov-1', $m->provider_ref);
        $this->assertSame(1, $m->attempts);
    }

    public function test_failed_gateway_marks_message_failed(): void
    {
        SmsGateway::create(['provider' => 'generic', 'enabled' => false]); // not configured
        $m = MessagingService::compose(['phone' => '0712345678', 'body' => 'Hi']);
        MessagingService::send($m);
        $this->assertSame('failed', $m->fresh()->status);
    }

    public function test_idempotency_prevents_duplicates(): void
    {
        $this->gateway();
        $a = MessagingService::compose(['phone' => '0712345678', 'body' => 'Hi', 'idempotency_key' => 'k1']);
        $b = MessagingService::compose(['phone' => '0712345678', 'body' => 'Hi', 'idempotency_key' => 'k1']);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Message::count());
    }

    public function test_template_unresolved_variable_never_sends(): void
    {
        $t = MessageTemplate::create(['key' => 't1', 'name' => 'T', 'category' => 'general', 'body' => 'Hi {{name}} {{missing}}']);
        $this->expectException(\RuntimeException::class);
        $t->render(['name' => 'X']);
    }

    public function test_automatic_rule_disabled_sends_nothing(): void
    {
        $this->gateway();
        NotificationRule::create(['event_key' => 'payment.received', 'label' => 'x', 'template_id' => null, 'enabled' => false]);
        $this->assertNull(MessagingService::notifyEvent('payment.received', '0712345678', []));
        $this->assertSame(0, Message::count());
    }

    public function test_automatic_rule_enabled_sends_once(): void
    {
        $this->gateway();
        Http::fake(['api.test/*' => Http::response(['id' => 'x'], 200)]);
        $tpl = MessageTemplate::create(['key' => 'payment.received', 'name' => 'P', 'category' => 'billing', 'body' => 'Paid {{amount_paid}}']);
        NotificationRule::create(['event_key' => 'payment.received', 'label' => 'x', 'template_id' => $tpl->id, 'enabled' => true]);

        MessagingService::notifyEvent('payment.received', '0712345678', ['amount_paid' => '1000'], ['dedupe' => 'p1']);
        MessagingService::notifyEvent('payment.received', '0712345678', ['amount_paid' => '1000'], ['dedupe' => 'p1']);
        $this->assertSame(1, Message::count());
    }

    public function test_delivery_webhook_updates_status(): void
    {
        $g = $this->gateway();
        $g->update(['webhook_secret' => 'whsec']);
        Http::fake(['api.test/*' => Http::response(['message_id' => 'prov-9'], 200)]);
        $m = MessagingService::sendNow(['phone' => '0712345678', 'body' => 'Hi']);

        $this->postJson('/webhooks/sms/whsec', ['message_id' => 'prov-9', 'status' => 'delivered'])->assertOk();
        $this->assertSame('delivered', $m->fresh()->status);

        // wrong secret rejected
        $this->postJson('/webhooks/sms/wrong', ['message_id' => 'prov-9', 'status' => 'failed'])->assertForbidden();
        $this->assertSame('delivered', $m->fresh()->status);
    }

    public function test_opted_out_recipient_rejected(): void
    {
        $this->gateway();
        \App\Models\CommunicationConsent::create(['phone' => '+255712345678', 'sms_opt_out' => true]);
        $this->expectException(\RuntimeException::class);
        MessagingService::compose(['phone' => '0712345678', 'body' => 'Hi']);
    }

    public function test_staff_cannot_access_gateway_settings(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        $this->actingAs($staff)->get(route('admin.communication.gateway'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.communication.index'))->assertForbidden();
    }

    public function test_credentials_are_masked_and_encrypted(): void
    {
        $g = $this->gateway();
        $this->assertStringNotContainsString('secret-key', $g->getAttributes()['api_key']); // stored encrypted
        $this->assertSame('secret-key', $g->api_key); // decrypts on read
        $admin = $this->admin();
        $resp = $this->actingAs($admin)->get(route('admin.communication.gateway'));
        $resp->assertOk();
        $resp->assertDontSee('secret-key'); // never rendered
    }

    public function test_cost_calculation(): void
    {
        $this->gateway(); // 25 per segment
        $this->assertSame(1, MessagingService::segments(str_repeat('a', 160)));
        $this->assertSame(2, MessagingService::segments(str_repeat('a', 161)));
        $cost = MessagingService::cost(str_repeat('a', 200));
        $this->assertSame('50.0000', $cost);
    }
}
