<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'message_reference', 'idempotency_key', 'template_id', 'event_key', 'type',
        'recipient_name', 'phone', 'patient_id', 'customer_id', 'body', 'segments',
        'cost', 'status', 'provider', 'provider_ref', 'provider_response',
        'failure_reason', 'attempts', 'scheduled_at', 'sent_at', 'delivered_at',
        'campaign_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'cost' => 'decimal:4'];
    }

    public function template() { return $this->belongsTo(MessageTemplate::class); }

    public function patient() { return $this->belongsTo(Patient::class); }

    public function customer() { return $this->belongsTo(Customer::class); }

    public function events() { return $this->hasMany(MessageEvent::class)->latest(); }

    public function setStatus(string $status, ?string $detail = null): void
    {
        $attrs = ['status' => $status];
        if ($status === 'delivered') {
            $attrs['delivered_at'] = now();
        }
        if (in_array($status, ['sent', 'submitted'])) {
            $attrs['sent_at'] = now();
        }
        $this->update($attrs);
        MessageEvent::create(['message_id' => $this->id, 'status' => $status, 'detail' => $detail]);
    }
}
