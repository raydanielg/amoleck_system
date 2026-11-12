<?php

namespace App\Services;

use App\Models\FulfilmentEvent;
use App\Models\ProductFulfilment;
use App\Models\ServiceFulfilment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfilmentService
{
    /** Advance a service fulfilment to a later step. */
    public static function advanceService(ServiceFulfilment $f, string $step, ?string $notes, ?int $assignedTo, ?string $scheduledDate, int $userId): void
    {
        DB::transaction(function () use ($f, $step, $notes, $assignedTo, $scheduledDate, $userId) {
            $f = ServiceFulfilment::whereKey($f->id)->lockForUpdate()->firstOrFail();
            $idx = array_search($step, ServiceFulfilment::STEPS);
            if ($idx === false || $idx <= $f->stepIndex()) {
                throw new RuntimeException('Invalid or backwards step transition.');
            }

            $attrs = ['status' => $step];
            if ($notes !== null) {
                $attrs['notes'] = $notes;
            }
            if ($assignedTo) {
                $attrs['assigned_to'] = $assignedTo;
            }
            if ($scheduledDate) {
                $attrs['scheduled_date'] = $scheduledDate;
            }
            if ($step === 'in_progress') {
                $attrs['started_at'] = now();
            }
            if ($step === 'completed') {
                $attrs['completed_at'] = now();
                $attrs['completed_by'] = $userId;
            }
            $f->update($attrs);
            static::event('service', $f->id, $step, $notes, $userId);
        });
    }

    /** Advance a product fulfilment order. */
    public static function advanceProduct(ProductFulfilment $f, string $step, ?string $notes, array $delivery, int $userId): void
    {
        DB::transaction(function () use ($f, $step, $notes, $delivery, $userId) {
            $f = ProductFulfilment::whereKey($f->id)->lockForUpdate()->firstOrFail();
            $idx = array_search($step, ProductFulfilment::STEPS);
            if ($idx === false || $idx <= $f->stepIndex()) {
                throw new RuntimeException('Invalid or backwards step transition.');
            }
            if ($step === 'delivered' && empty($delivery['proof_note'])) {
                throw new RuntimeException('Proof of delivery / collection confirmation is required.');
            }
            if ($step === 'dispatched' && empty($f->delivery_method) && empty($delivery['delivery_method'])) {
                throw new RuntimeException('Select a delivery method before dispatch.');
            }

            $attrs = ['status' => $step];
            foreach (['delivery_method', 'delivery_address', 'recipient_name', 'recipient_phone', 'courier', 'expected_delivery_date', 'proof_note', 'assigned_to'] as $k) {
                if (array_key_exists($k, $delivery) && $delivery[$k] !== null) {
                    $attrs[$k] = $delivery[$k];
                }
            }
            if ($step === 'dispatched') {
                $attrs['dispatched_at'] = now();
            }
            if ($step === 'delivered') {
                $attrs['delivered_at'] = now();
            }
            $f->update($attrs);
            static::event('product', $f->id, $step, $notes, $userId);

            $map = ['ready_dispatch' => 'order.ready', 'dispatched' => 'order.dispatched', 'delivered' => 'order.delivered'];
            if (isset($map[$step])) {
                try {
                    $phone = $f->patient?->phone ?? $f->customer?->phone ?? $f->recipient_phone;
                    $name = $f->patient?->full_name ?? $f->customer?->name ?? $f->recipient_name;
                    \App\Services\Messaging\MessagingService::notifyEvent($map[$step], $phone, [
                        'customer_name' => $name, 'patient_name' => $name, 'tracking_number' => $f->tracking_reference,
                        'clinic_name' => \App\Support\Settings::get('clinic_name'),
                    ], ['patient_id' => $f->patient_id, 'customer_id' => $f->customer_id, 'dedupe' => $f->id.$step]);
                } catch (\Throwable) {}
            }
        });
    }

    /** Admin correction — records a backwards step with mandatory reason. */
    public static function correct(string $type, int $id, string $step, string $reason, int $userId): void
    {
        DB::transaction(function () use ($type, $id, $step, $reason, $userId) {
            $model = $type === 'service' ? ServiceFulfilment::class : ProductFulfilment::class;
            $f = $model::whereKey($id)->lockForUpdate()->firstOrFail();
            $f->update(['status' => $step]);
            static::event($type, $id, 'correction:'.$step, 'Status corrected to '.$step.'. '.$reason, $userId);
            AuditLogger::log('fulfilment.corrected', $type.'_fulfilment', $id, null, ['step' => $step, 'reason' => $reason]);
        });
    }

    private static function event(string $type, int $id, string $step, ?string $notes, int $userId): void
    {
        FulfilmentEvent::create(['fulfilment_type' => $type, 'fulfilment_id' => $id, 'step' => $step, 'notes' => $notes, 'user_id' => $userId]);
    }
}
