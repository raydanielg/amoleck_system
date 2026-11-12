<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleRefund;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(private StockService $stock) {}

    /**
     * Create a sale: items, totals, optional first payment, stock deduction.
     * items: [['product_id' => int, 'quantity' => int, 'unit_price' => float|null]]
     */
    public function createSale(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $allowBackorder = Settings::get('pos_allow_backorders', false);
            $subtotal = '0.00';
            $items = [];

            foreach ($data['items'] as $row) {
                $product = Product::whereKey($row['product_id'])->lockForUpdate()->firstOrFail();
                if (! $product->isActive()) {
                    throw new RuntimeException("{$product->name} is not active.");
                }
                $qty = (int) $row['quantity'];
                $price = isset($row['unit_price']) ? (string) $row['unit_price'] : (string) $product->selling_price;

                if (! $allowBackorder && $product->stock_quantity < $qty) {
                    throw new RuntimeException("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}.");
                }

                $lineTotal = bcmul($price, (string) $qty, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);
                $items[] = compact('product', 'qty', 'price', 'lineTotal');
            }

            $discount = isset($data['discount_amount']) ? (string) $data['discount_amount'] : '0.00';
            if (bccomp($discount, $subtotal, 2) > 0) {
                throw new RuntimeException('Discount cannot exceed subtotal.');
            }
            if (bccomp($discount, '0', 2) > 0 && empty($data['discount_reason'])) {
                throw new RuntimeException('A discount reason is required.');
            }

            $taxEnabled = Settings::get('tax_enabled', false);
            $taxPercent = $taxEnabled ? (string) Settings::get('tax_percent', '0') : '0';
            $taxable = bcsub($subtotal, $discount, 2);
            $taxAmount = bcmul($taxable, bcdiv($taxPercent, '100', 4), 2);
            $total = bcadd($taxable, $taxAmount, 2);

            $sale = Sale::create([
                'sale_reference' => $this->nextRef('sale_prefix', 'SAL'),
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'status' => Sale::STATUS_PENDING,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'discount_reason' => $data['discount_reason'] ?? null,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'amount_paid' => '0.00',
                'balance' => $total,
                'notes' => $data['notes'] ?? null,
                'sold_by' => $userId,
            ]);

            foreach ($items as $i) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $i['product']->id,
                    'product_name' => $i['product']->name,
                    'sku' => $i['product']->sku,
                    'unit_price' => $i['price'],
                    'unit_cost' => $i['product']->purchase_price,
                    'quantity' => $i['qty'],
                    'line_total' => $i['lineTotal'],
                ]);

                $i['product']->update(['stock_quantity' => $i['product']->stock_quantity - $i['qty']]);
                StockMovement::create([
                    'product_id' => $i['product']->id,
                    'type' => StockMovement::TYPE_SOLD,
                    'quantity' => -$i['qty'],
                    'stock_after' => $i['product']->stock_quantity,
                    'reference' => $sale->sale_reference,
                    'reason' => 'POS sale',
                    'user_id' => $userId,
                ]);

                if ($i['product']->min_stock_level > 0 && $i['product']->stock_quantity <= $i['product']->min_stock_level) {
                    $msg = $i['product']->stock_quantity <= 0 ? "{$i['product']->name} is out of stock." : "{$i['product']->name} is low on stock ({$i['product']->stock_quantity} left).";
                    NotificationService::notifyAdmins('stock.low', 'Low stock alert', $msg, route('admin.inventory.index'));
                }
            }

            $paymentAmount = (string) ($data['amount_paid'] ?? $total);
            $payNow = ($data['pay_now'] ?? true);
            if ($payNow && bccomp($paymentAmount, '0', 2) > 0) {
                $this->addPayment($sale, $paymentAmount, $data['payment_method'] ?? 'Cash', $userId);
            }
            $sale->refreshPaymentState();

            \App\Models\ProductFulfilment::create([
                'tracking_reference' => \App\Support\Settings::nextNumber('delivery_prefix', 'delivery_seq', 'DLV'),
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'status' => 'received',
            ]);

            NotificationService::notifyAdmins('sale.recorded', 'New sale '.$sale->sale_reference, "Sale of {$sale->total} recorded.", route('admin.sales.show', $sale));

            return $sale;
        });
    }

    public function addPayment(Sale $sale, string $amount, string $method, int $userId): SalePayment
    {
        return DB::transaction(function () use ($sale, $amount, $method, $userId) {
            try {
                $m = $method === 'cash' ? \App\Services\AccountingService::CASH : \App\Services\AccountingService::BANK;
                \App\Services\AccountingService::post('SALEPMT-'.$sale->sale_reference.'-'.$amount.'-'.$userId.'-'.$sale->payments()->count(), 'Sale payment '.$sale->sale_reference, [
                    ['account' => $m, 'debit' => $amount],
                    ['account' => \App\Services\AccountingService::PRODUCT_REVENUE, 'credit' => $amount],
                ], 'sale_payment', $sale->id);
            } catch (\Throwable) {}
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->first();
            if ($sale->status === Sale::STATUS_CANCELLED) {
                throw new RuntimeException('Cannot pay a cancelled sale.');
            }
            if (bccomp($amount, (string) $sale->balance, 2) > 0) {
                throw new RuntimeException('Payment exceeds outstanding balance.');
            }
            if (bccomp($amount, '0', 2) <= 0) {
                throw new RuntimeException('Payment amount must be positive.');
            }

            $payment = SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_date' => now()->toDateString(),
                'status' => SalePayment::STATUS_COMPLETED,
                'received_by' => $userId,
            ]);
            $sale->refreshPaymentState();

            return $payment;
        });
    }

    public function refund(Sale $sale, string $amount, string $reason, int $userId): SaleRefund
    {
        return DB::transaction(function () use ($sale, $amount, $reason, $userId) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->first();
            $netPaid = bcsub((string) $sale->payments()->where('status', 'completed')->sum('amount'), (string) $sale->refunds()->sum('amount'), 2);
            if (bccomp($amount, $netPaid, 2) > 0) {
                throw new RuntimeException('Refund exceeds amount paid.');
            }
            if (bccomp($amount, '0', 2) <= 0) {
                throw new RuntimeException('Refund amount must be positive.');
            }

            $refund = SaleRefund::create([
                'refund_reference' => $this->nextRef('refund_prefix', 'RFD'),
                'sale_id' => $sale->id,
                'amount' => $amount,
                'reason' => $reason,
                'refunded_by' => $userId,
            ]);
            $sale->refreshPaymentState();
            NotificationService::notifyAdmins('refund.recorded', 'Sale refund '.$refund->refund_reference, "Refund of {$amount} on {$sale->sale_reference}.", route('admin.sales.show', $sale));

            return $refund;
        });
    }

    /** Return items to stock. items: [['sale_item_id' => int, 'quantity' => int]] */
    public function processReturn(Sale $sale, array $items, string $reason, bool $restock, int $userId): SaleReturn
    {
        return DB::transaction(function () use ($sale, $items, $reason, $restock, $userId) {
            $return = SaleReturn::create([
                'return_reference' => $this->nextRef('return_prefix', 'RET'),
                'sale_id' => $sale->id,
                'reason' => $reason,
                'recorded_by' => $userId,
            ]);

            foreach ($items as $row) {
                $saleItem = $sale->items()->whereKey($row['sale_item_id'])->firstOrFail();
                $qty = (int) $row['quantity'];
                $alreadyReturned = SaleReturnItem::whereHas('saleReturn', fn ($q) => $q->where('sale_id', $sale->id))
                    ->where('product_id', $saleItem->product_id)->sum('quantity');
                if ($qty < 1 || $qty + $alreadyReturned > $saleItem->quantity) {
                    throw new RuntimeException("Invalid return quantity for {$saleItem->product_name}.");
                }

                SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $qty,
                    'restocked' => $restock,
                ]);

                if ($restock && $saleItem->product_id) {
                    $product = Product::whereKey($saleItem->product_id)->lockForUpdate()->first();
                    $product->increment('stock_quantity', $qty);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => StockMovement::TYPE_RETURNED,
                        'quantity' => $qty,
                        'stock_after' => $product->fresh()->stock_quantity,
                        'reference' => $return->return_reference,
                        'reason' => 'Sale return',
                        'user_id' => $userId,
                    ]);
                }
            }

            return $return;
        });
    }

    public function cancel(Sale $sale, int $userId): void
    {
        DB::transaction(function () use ($sale, $userId) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->first();
            if (bccomp((string) $sale->amount_paid, '0', 2) > 0) {
                throw new RuntimeException('Cannot cancel a sale with payments. Refund first.');
            }
            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                    $product->increment('stock_quantity', $item->quantity);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => StockMovement::TYPE_RETURNED,
                        'quantity' => $item->quantity,
                        'stock_after' => $product->fresh()->stock_quantity,
                        'reference' => $sale->sale_reference,
                        'reason' => 'Sale cancelled',
                        'user_id' => $userId,
                    ]);
                }
            }
            $sale->update(['status' => Sale::STATUS_CANCELLED, 'balance' => '0.00']);
        });
    }

    private function nextRef(string $key, string $default): string
    {
        $prefix = Settings::get($key, $default);
        $next = (int) Settings::get($key.'_seq', 0) + 1;
        Settings::set($key.'_seq', $next);

        return $prefix.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
