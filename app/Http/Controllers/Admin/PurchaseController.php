<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier')->withCount('items')->latest()->paginate(25);

        return view('admin.supplies.purchases.index', compact('purchases'));
    }

    public function create()
    {
        return view('admin.supplies.purchases.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'purchase_price', 'stock_quantity']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $purchase = DB::transaction(function () use ($data, $request) {
            $total = '0.00';
            $purchase = Purchase::create([
                'purchase_reference' => Settings::nextNumber('purchase_prefix', 'purchase_seq', 'PUR'),
                'supplier_id' => $data['supplier_id'] ?? null,
                'status' => Purchase::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            foreach ($data['items'] as $row) {
                $line = bcmul((string) $row['unit_cost'], (string) $row['quantity'], 2);
                $total = bcadd($total, $line, 2);
                $purchase->items()->create([
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'],
                    'line_total' => $line,
                ]);
            }
            $purchase->update(['total' => $total]);

            return $purchase;
        });

        AuditLogger::log('purchase.created', 'purchase', $purchase->id, null, ['reference' => $purchase->purchase_reference, 'total' => (string) $purchase->total]);

        if ($request->boolean('receive_now')) {
            return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase created. Receive goods below.');
        }

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase order created.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product', 'supplier', 'creator']);

        return view('admin.supplies.purchases.show', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_DRAFT) {
            return back()->with('error', 'Purchase already '.$purchase->status.'.');
        }

        DB::transaction(function () use ($purchase, $request) {
            foreach ($purchase->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                $product->increment('stock_quantity', $item->quantity);
                $product->update(['purchase_price' => $item->unit_cost]);
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovement::TYPE_PURCHASE_RECEIVED,
                    'quantity' => $item->quantity,
                    'stock_after' => $product->fresh()->stock_quantity,
                    'reference' => $purchase->purchase_reference,
                    'reason' => 'Goods received',
                    'user_id' => $request->user()->id,
                ]);
                $item->update(['quantity_received' => $item->quantity]);
            }
            $purchase->update(['status' => Purchase::STATUS_RECEIVED, 'received_at' => now()]);
        });

        AuditLogger::log('purchase.received', 'purchase', $purchase->id);
        NotificationService::notifyAdmins('purchase.received', 'Goods received', "Purchase {$purchase->purchase_reference} received into stock.", route('admin.purchases.show', $purchase));

        return back()->with('success', 'Goods received into stock.');
    }

    public function cancel(Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_DRAFT) {
            return back()->with('error', 'Only draft purchases can be cancelled.');
        }
        $purchase->update(['status' => Purchase::STATUS_CANCELLED]);
        AuditLogger::log('purchase.cancelled', 'purchase', $purchase->id);

        return back()->with('success', 'Purchase cancelled.');
    }
}
