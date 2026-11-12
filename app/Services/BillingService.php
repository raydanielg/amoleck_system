<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Service;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    /**
     * Create an invoice with item snapshots. Must stay stable even if service prices change later.
     *
     * @param  array<int, array{service_id:int|null, name:string, code:string|null, unit_price:string, quantity:int}>  $items
     */
    public static function createInvoice(Patient $patient, array $items, ?int $visitId, string $issueDate, string $discountAmount, ?string $discountReason, ?string $notes, int $userId, array $productItems = [], ?string $dueDate = null): Invoice
    {
        return DB::transaction(function () use ($patient, $items, $visitId, $issueDate, $discountAmount, $discountReason, $notes, $userId, $productItems, $dueDate) {
            $subtotal = '0';
            foreach ($items as $item) {
                $lineTotal = bcmul($item['unit_price'], (string) $item['quantity'], 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);
            }

            // Resolve product items (price + cost snapshots, stock check)
            $resolvedProducts = [];
            foreach ($productItems as $row) {
                $product = \App\Models\Product::whereKey($row['product_id'])->lockForUpdate()->first();
                if (! $product || ! $product->isActive()) {
                    continue;
                }
                $qty = max(1, (int) ($row['quantity'] ?? 1));
                if (! Settings::bool('pos_allow_backorders') && $product->stock_quantity < $qty) {
                    throw ValidationException::withMessages(['products' => "Insufficient stock for {$product->name} ({$product->stock_quantity} available)."]);
                }
                $resolvedProducts[] = ['product' => $product, 'quantity' => $qty];
                $subtotal = bcadd($subtotal, bcmul((string) $product->selling_price, (string) $qty, 2), 2);
            }

            $discount = bccomp($discountAmount, '0', 2) > 0 ? $discountAmount : '0.00';
            if (bccomp($discount, $subtotal, 2) > 0) {
                throw ValidationException::withMessages(['discount_amount' => 'Discount cannot exceed the invoice subtotal.']);
            }
            if (bccomp($discount, '0', 2) > 0 && ! $discountReason) {
                throw ValidationException::withMessages(['discount_reason' => 'A reason is required when applying a discount.']);
            }

            $total = bcsub($subtotal, $discount, 2);

            $invoice = Invoice::create([
                'invoice_reference' => Settings::nextNumber('invoice_reference_prefix', 'invoice_reference_next', 'INV'),
                'patient_id' => $patient->id,
                'visit_id' => $visitId,
                'issued_by' => $userId,
                'issue_date' => $issueDate,
                'status' => Invoice::STATUS_ISSUED,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'discount_reason' => $discount ? $discountReason : null,
                'total' => $total,
                'amount_paid' => '0.00',
                'balance' => $total,
                'notes' => $notes,
                'type' => $items && $resolvedProducts ? 'combined' : ($resolvedProducts ? 'product' : 'service'),
                'due_date' => $dueDate ?: now()->addDays(Settings::int('invoice_due_days', 7))->toDateString(),
                'terms' => Settings::get('invoice_terms'),
            ]);

            foreach ($items as $item) {
                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $item['service_id'],
                    'service_name' => $item['name'],
                    'service_code' => $item['code'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => bcmul($item['unit_price'], (string) $item['quantity'], 2),
                    'notes' => $item['notes'] ?? null,
                ]);

                \App\Models\ServiceFulfilment::create([
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'patient_id' => $patient->id,
                    'service_id' => $item['service_id'],
                    'status' => 'requested',
                ]);
            }

            foreach ($resolvedProducts as $row) {
                $product = $row['product'];
                $qty = $row['quantity'];
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'service_name' => $product->name,
                    'service_code' => $product->sku,
                    'unit_price' => (string) $product->selling_price,
                    'unit_cost' => (string) $product->purchase_price,
                    'quantity' => $qty,
                    'line_total' => bcmul((string) $product->selling_price, (string) $qty, 2),
                ]);
                $product->decrement('stock_quantity', $qty);
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type' => \App\Models\StockMovement::TYPE_SOLD,
                    'quantity' => -$qty,
                    'stock_after' => $product->fresh()->stock_quantity,
                    'reference' => $invoice->invoice_reference,
                    'reason' => 'Invoice product line',
                    'user_id' => $userId,
                ]);
            }

            if ($resolvedProducts) {
                \App\Models\ProductFulfilment::create([
                    'tracking_reference' => Settings::nextNumber('delivery_prefix', 'delivery_seq', 'DLV'),
                    'invoice_id' => $invoice->id,
                    'patient_id' => $patient->id,
                    'status' => 'received',
                ]);
            }

            AuditLogger::log('invoice.created', 'invoice', $invoice->id, null, $invoice->only(['invoice_reference', 'patient_id', 'total']));

            NotificationService::notifyAdmins('invoice.created', 'Invoice created', "Invoice {$invoice->invoice_reference} for {$patient->full_name} ({$invoice->total})", route('admin.invoices.show', $invoice));

            try {
                \App\Services\AccountingService::post('INV-'.$invoice->invoice_reference, 'Invoice '.$invoice->invoice_reference.' issued', [
                    ['account' => \App\Services\AccountingService::AR, 'debit' => $invoice->total],
                    ['account' => \App\Services\AccountingService::SERVICE_REVENUE, 'credit' => $invoice->items()->where('item_type', 'service')->sum('line_total') ?: 0],
                    ['account' => \App\Services\AccountingService::PRODUCT_REVENUE, 'credit' => $invoice->items()->where('item_type', 'product')->sum('line_total') ?: 0],
                ], 'invoice', $invoice->id, $invoice->issued_at ?? $invoice->created_at);
            } catch (\Throwable) {}

            try {
                \App\Services\Messaging\MessagingService::notifyEvent('invoice.issued', $patient->phone, [
                    'patient_name' => $patient->full_name, 'invoice_number' => $invoice->invoice_reference,
                    'invoice_amount' => $invoice->total, 'due_date' => $invoice->due_date?->format('d/m/Y') ?? '',
                    'clinic_name' => Settings::get('clinic_name'), 'clinic_phone' => Settings::get('clinic_phone'),
                ], ['patient_id' => $patient->id, 'dedupe' => $invoice->id]);
            } catch (\Throwable) {}

            return $invoice;
        });
    }

    /** Build snapshot items from an array of [service_id, quantity] pairs. */
    public static function itemsFromServices(array $serviceQuantities): array
    {
        $items = [];
        foreach ($serviceQuantities as $serviceId => $quantity) {
            $service = Service::find($serviceId);
            if (! $service) {
                continue;
            }
            $items[] = [
                'service_id' => $service->id,
                'name' => $service->name,
                'code' => $service->service_code,
                'unit_price' => (string) $service->standard_price,
                'quantity' => max(1, (int) $quantity),
            ];
        }

        return $items;
    }

    public static function recordPayment(Invoice $invoice, string $amount, string $method, string $paymentDate, ?string $notes, int $userId, ?string $transactionReference = null, ?string $proofNote = null): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $paymentDate, $notes, $userId, $transactionReference, $proofNote) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->isVoided()) {
                throw ValidationException::withMessages(['amount' => 'Cannot record payment on a voided invoice.']);
            }
            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }
            if (bccomp($amount, (string) $invoice->balance, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding balance ('.$invoice->balance.').']);
            }

            $payment = Payment::create([
                'payment_reference' => Settings::nextNumber('payment_reference_prefix', 'payment_reference_next', 'PAY'),
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_date' => $paymentDate,
                'status' => Payment::STATUS_COMPLETED,
                'received_by' => $userId,
                'notes' => $notes,
                'transaction_reference' => $transactionReference,
                'verification_status' => static::requiresVerification($method) ? Payment::VERIFICATION_PENDING : Payment::VERIFICATION_VERIFIED,
                'proof_note' => $proofNote,
            ]);

            $invoice->refreshPaymentState();

            AuditLogger::log('payment.recorded', 'payment', $payment->id, null, $payment->only(['payment_reference', 'invoice_id', 'amount', 'payment_method']));

            NotificationService::notifyAdmins('payment.recorded', 'Payment recorded', "Payment {$payment->payment_reference} of {$payment->amount} on {$invoice->invoice_reference}", route('admin.payments.show', $payment));

            try {
                \App\Services\Messaging\MessagingService::notifyEvent('payment.received', $invoice->patient?->phone, [
                    'patient_name' => $invoice->patient?->full_name, 'invoice_number' => $invoice->invoice_reference,
                    'amount_paid' => $payment->amount, 'outstanding_balance' => $invoice->fresh()->balance,
                    'payment_reference' => $payment->payment_reference, 'receipt_number' => $payment->payment_reference,
                    'clinic_name' => Settings::get('clinic_name'),
                ], ['patient_id' => $invoice->patient_id, 'dedupe' => $payment->id]);
            } catch (\Throwable) {}

            return $payment;
        });
    }

    public static function refundPayment(Payment $payment, string $amount, string $reason, int $userId): Refund
    {
        return DB::transaction(function () use ($payment, $amount, $reason, $userId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== Payment::STATUS_COMPLETED) {
                throw ValidationException::withMessages(['amount' => 'Cannot refund a voided payment.']);
            }
            $refundable = $payment->refundableAmount();
            if (bccomp($amount, '0', 2) <= 0 || bccomp($amount, $refundable, 2) > 0) {
                throw ValidationException::withMessages(['amount' => "Refund must be between 0.01 and {$refundable}."]);
            }

            $refund = Refund::create([
                'refund_reference' => Settings::nextNumber('refund_reference_prefix', 'refund_reference_next', 'RFD'),
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => Refund::STATUS_REFUNDED,
                'refunded_by' => $userId,
            ]);

            $payment->invoice->refreshPaymentState();

            AuditLogger::log('payment.refunded', 'refund', $refund->id, null, $refund->only(['refund_reference', 'payment_id', 'amount', 'reason']));

            NotificationService::notifyAdmins('refund.recorded', 'Refund recorded', "Refund {$refund->refund_reference} of {$refund->amount} on {$payment->payment_reference}", route('admin.refunds.index'));

            return $refund;
        });
    }

    public static function voidInvoice(Invoice $invoice, string $reason, int $userId): void
    {
        DB::transaction(function () use ($invoice, $reason) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $hasPayments = $invoice->payments()->where('status', Payment::STATUS_COMPLETED)->exists();
            if ($hasPayments) {
                throw ValidationException::withMessages(['void_reason' => 'Invoice has payments. Refund or void the payments first.']);
            }

            foreach ($invoice->items()->where('item_type', 'product')->get() as $item) {
                if ($item->product_id) {
                    $product = \App\Models\Product::whereKey($item->product_id)->lockForUpdate()->first();
                    $product->increment('stock_quantity', $item->quantity);
                    \App\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'type' => \App\Models\StockMovement::TYPE_RETURNED,
                        'quantity' => $item->quantity,
                        'stock_after' => $product->fresh()->stock_quantity,
                        'reference' => $invoice->invoice_reference,
                        'reason' => 'Invoice voided',
                        'user_id' => $userId,
                    ]);
                }
            }

            $invoice->update([
                'status' => Invoice::STATUS_VOIDED,
                'void_reason' => $reason,
            ]);

            AuditLogger::log('invoice.voided', 'invoice', $invoice->id, null, ['reason' => $reason]);
        });
    }

    /** Methods that require manual verification before counting toward balance. */
    public static function requiresVerification(string $method): bool
    {
        $list = array_map('trim', explode(',', (string) Settings::get('payment_verification_methods', 'Bank Transfer,Office Payment Number')));

        return in_array($method, $list, true);
    }

    public static function verifyPayment(Payment $payment, int $userId): void
    {
        DB::transaction(function () use ($payment, $userId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->verification_status === Payment::VERIFICATION_VERIFIED) {
                return;
            }
            $payment->update([
                'verification_status' => Payment::VERIFICATION_VERIFIED,
                'verified_by' => $userId,
                'verified_at' => now(),
            ]);
            $payment->invoice->refreshPaymentState();
            AuditLogger::log('payment.verified', 'payment', $payment->id);

            try {
                $m = $payment->method === 'cash' ? \App\Services\AccountingService::CASH : \App\Services\AccountingService::BANK;
                \App\Services\AccountingService::post('PMT-'.$payment->payment_reference, 'Verified payment '.$payment->payment_reference, [
                    ['account' => $m, 'debit' => $payment->amount],
                    ['account' => \App\Services\AccountingService::AR, 'credit' => $payment->amount],
                ], 'payment', $payment->id, $payment->paid_at ?? null);
            } catch (\Throwable) {}

            try {
                $invoice = $payment->invoice;
                \App\Services\Messaging\MessagingService::notifyEvent('payment.verified', $invoice->patient?->phone, [
                    'patient_name' => $invoice->patient?->full_name, 'invoice_number' => $invoice->invoice_reference,
                    'amount_paid' => $payment->amount, 'outstanding_balance' => $invoice->fresh()->balance,
                    'clinic_name' => Settings::get('clinic_name'),
                ], ['patient_id' => $invoice->patient_id, 'dedupe' => 'v'.$payment->id]);
            } catch (\Throwable) {}
        });
    }

    public static function rejectPayment(Payment $payment, int $userId): void
    {
        DB::transaction(function () use ($payment, $userId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $payment->update(['verification_status' => Payment::VERIFICATION_REJECTED]);
            $payment->invoice->refreshPaymentState();
            AuditLogger::log('payment.rejected', 'payment', $payment->id);
        });
    }
}
