<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\NotificationService;
use App\Services\StockService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request)
    {
        $products = Product::with('category')->orderBy('name')->paginate(30)->withQueryString();

        $valuation = Product::selectRaw('COALESCE(SUM(stock_quantity * purchase_price),0) as cost_value, COALESCE(SUM(stock_quantity * selling_price),0) as retail_value')->first();
        $lowStock = Product::where('status', 'active')->where('min_stock_level', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level')->where('stock_quantity', '>', 0)->count();
        $outOfStock = Product::where('status', 'active')->where('stock_quantity', '<=', 0)->count();
        $movements = StockMovement::with(['product', 'user'])->latest()->limit(20)->get();

        return view('admin.supplies.inventory.index', compact('products', 'valuation', 'lowStock', 'outOfStock', 'movements'));
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with(['product', 'user'])->latest();
        if ($pid = $request->input('product')) {
            $query->where('product_id', $pid);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        $movements = $query->paginate(40)->withQueryString();
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.supplies.inventory.movements', compact('movements', 'products'));
    }

    public function receive(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:80',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->stock->move($product, $data['quantity'], StockMovement::TYPE_RECEIVED, $data['reference'] ?? null, $data['reason'] ?? 'Stock received', $request->user()->id);

        if (! empty($data['unit_cost']) && $data['unit_cost'] != $product->purchase_price) {
            $product->update(['purchase_price' => $data['unit_cost']]);
        }

        return back()->with('success', "Received {$data['quantity']} {$product->unit}(s) of {$product->name}.");
    }

    public function adjust(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->stock->move($product, $data['quantity'], StockMovement::TYPE_ADJUSTED, null, $data['reason'], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock adjusted.');
    }
}
