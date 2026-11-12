<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFulfilment extends Model
{
    public const STEPS = ['requested', 'scheduled', 'preparing', 'ready', 'in_progress', 'completed', 'closed'];

    protected $fillable = [
        'invoice_id', 'invoice_item_id', 'patient_id', 'service_id', 'status',
        'scheduled_date', 'assigned_to', 'notes', 'started_at', 'completed_at', 'completed_by',
    ];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }

    public function item() { return $this->belongsTo(InvoiceItem::class, 'invoice_item_id'); }

    public function patient() { return $this->belongsTo(Patient::class); }

    public function service() { return $this->belongsTo(Service::class); }

    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }

    public function events()
    {
        return $this->hasMany(FulfilmentEvent::class, 'fulfilment_id')->where('fulfilment_type', 'service')->latest();
    }

    public function stepIndex(): int { return (int) array_search($this->status, self::STEPS); }
}
