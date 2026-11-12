<?php

namespace App\Services\Messaging;

use App\Models\CommunicationConsent;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\NotificationRule;
use App\Models\SmsGateway;
use Illuminate\Support\Str;

class MessagingService
{
    /** Normalize TZ phone numbers to international format. */
    public static function normalizePhone(string $phone, ?string $countryCode = null): ?string
    {
        $cc = $countryCode ?: (SmsGateway::current()->country_code ?: '+255');
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }
        if (str_starts_with($digits, '255')) {
            return '+'.$digits;
        }

        return $cc.$digits;
    }

    public static function segments(string $body): int
    {
        $len = mb_strlen($body);

        return max(1, (int) ceil($len / 160));
    }

    public static function cost(string $body): ?string
    {
        $per = SmsGateway::current()->cost_per_segment;

        return $per !== null ? bcmul((string) static::segments($body), (string) $per, 4) : null;
    }

    /** Queue/create a message record (idempotent). */
    public static function compose(array $data): Message
    {
        $key = $data['idempotency_key'] ?? (string) Str::uuid();
        if ($existing = Message::where('idempotency_key', $key)->first()) {
            return $existing;
        }

        $phone = static::normalizePhone($data['phone']);
        if (! $phone) {
            throw new \RuntimeException('Invalid phone number.');
        }
        if (CommunicationConsent::optedOut($phone)) {
            throw new \RuntimeException('Recipient has opted out of SMS.');
        }

        return Message::create([
            'message_reference' => 'MSG-'.strtoupper(Str::random(8)),
            'idempotency_key' => $key,
            'template_id' => $data['template_id'] ?? null,
            'event_key' => $data['event_key'] ?? null,
            'type' => $data['type'] ?? 'manual',
            'recipient_name' => $data['recipient_name'] ?? null,
            'phone' => $phone,
            'patient_id' => $data['patient_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'body' => $data['body'],
            'segments' => static::segments($data['body']),
            'cost' => static::cost($data['body']),
            'status' => ! empty($data['scheduled_at']) ? 'scheduled' : 'queued',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    /** Submit a queued message to the configured gateway. */
    public static function send(Message $message): Message
    {
        $gateway = SmsGateway::current();
        if (! $gateway->enabled || ! $gateway->isConfigured()) {
            $message->update(['status' => 'failed', 'failure_reason' => 'Gateway not configured or disabled']);
            $message->events()->create(['status' => 'failed', 'detail' => 'Gateway not configured']);

            return $message;
        }

        $result = GatewayManager::adapter($gateway)->send($gateway, $message->phone, $message->body);

        $message->update([
            'provider' => $gateway->provider,
            'provider_ref' => $result['provider_ref'],
            'provider_response' => is_string($result['raw']) ? $result['raw'] : json_encode($result['raw']),
            'failure_reason' => $result['error'],
            'attempts' => $message->attempts + 1,
        ]);
        $message->setStatus($result['status'], $result['error'] ?? 'Submitted to gateway');

        return $message->fresh();
    }

    /** Compose + immediately send. */
    public static function sendNow(array $data): Message
    {
        return static::send(static::compose($data));
    }

    /** Fire an automatic notification for an event if a rule is enabled. */
    public static function notifyEvent(string $eventKey, ?string $phone, array $vars, array $meta = []): ?Message
    {
        $rule = NotificationRule::where('event_key', $eventKey)->where('enabled', true)->first();
        if (! $rule || ! $rule->template || ! $phone) {
            return null;
        }
        try {
            $body = $rule->template->render($vars);
        } catch (\RuntimeException) {
            return null; // never send unresolved placeholders
        }

        $data = array_merge($meta, [
            'phone' => $phone,
            'body' => $body,
            'template_id' => $rule->template_id,
            'event_key' => $eventKey,
            'type' => 'automatic',
            'idempotency_key' => $eventKey.':'.$phone.':'.($meta['dedupe'] ?? ''),
        ]);

        $scheduledAt = $rule->delay_minutes > 0 ? now()->addMinutes($rule->delay_minutes) : null;
        if ($scheduledAt) {
            $data['scheduled_at'] = $scheduledAt;

            return static::compose($data);
        }

        return static::sendNow($data);
    }
}
