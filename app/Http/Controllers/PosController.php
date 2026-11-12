<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\AuditLogger;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $products = Product::with('category')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'barcode', 'selling_price', 'stock_quantity', 'image_path', 'unit', 'product_category_id']);

        return view('pos.index', [
            'products' => $products,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
            'paymentMethods' => \App\Support\Settings::paymentMethods(),
            'taxEnabled' => \App\Support\Settings::bool('tax_enabled'),
            'taxPercent' => \App\Support\Settings::get('tax_percent', '0'),
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:160',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'amount_paid' => 'nullable|numeric|min:0',
            'pay_now' => 'nullable|boolean',
            'payment_method' => 'required|string|max:60',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $sale = $this->sales->createSale($data, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AuditLogger::log('sale.created', 'sale', $sale->id, null, ['reference' => $sale->sale_reference, 'total' => (string) $sale->total]);

        return redirect()->route('pos.receipt', $sale)->with('success', 'Sale '.$sale->sale_reference.' completed.');
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items', 'customer', 'seller', 'payments']);

        return view('pos.receipt', compact('sale'));
    }
}
