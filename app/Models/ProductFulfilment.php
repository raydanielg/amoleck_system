<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFulfilment extends Model
{
    public const STEPS = ['received', 'payment_verified', 'stock_confirmed', 'picking', 'quality_check', 'packaging', 'ready_dispatch', 'dispatched', 'delivered', 'completed'];

    protected $fillable = [
        'tracking_reference', 'invoice_id', 'sale_id', 'patient_id', 'customer_id', 'status',
        'delivery_method', 'delivery_address', 'recipient_name', 'recipient_phone', 'courier',
        'expected_delivery_date', 'dispatched_at', 'delivered_at', 'proof_note', 'assigned_to',
    ];

    protected function casts(): array
    {
        return ['expected_delivery_date' => 'date', 'dispatched_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }

    public function sale() { return $this->belongsTo(Sale::class); }

    public function patient() { return $this->belongsTo(Patient::class); }

    public function customer() { return $this->belongsTo(Customer::class); }

    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }

    public function events()
    {
        return $this->hasMany(FulfilmentEvent::class, 'fulfilment_id')->where('fulfilment_type', 'product')->latest();
    }

    public function stepIndex(): int { return (int) array_search($this->status, self::STEPS); }
}
