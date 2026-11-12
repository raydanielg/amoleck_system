<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier'])->latest();

        if ($s = $request->input('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")->orWhere('barcode', 'like', "%{$s}%"));
        }
        if ($cat = $request->input('category')) {
            $query->where('product_category_id', $cat);
        }
        if ($request->input('stock') === 'low') {
            $query->where('min_stock_level', '>', 0)->whereColumn('stock_quantity', '<=', 'min_stock_level');
        } elseif ($request->input('stock') === 'out') {
            $query->where('stock_quantity', '<=', 0);
        }

        $products = $query->paginate(25)->withQueryString();
        $categories = ProductCategory::active()->get();

        return view('admin.supplies.products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => 'required|string|max:60|unique:products,sku',
            'barcode' => 'nullable|string|max:60',
            'name' => 'required|string|max:160',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:30',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product = Product::create($data + ['created_by' => $request->user()->id]);
        AuditLogger::log('product.created', 'product', $product->id, null, $product->only(['sku', 'name', 'selling_price']));

        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => 'required|string|max:60|unique:products,sku,'.$product->id,
            'barcode' => 'nullable|string|max:60',
            'name' => 'required|string|max:160',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string|max:1000',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:30',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        // stock_quantity only changes via stock movements
        unset($data['stock_quantity']);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        $product->update($data);
        AuditLogger::log('product.updated', 'product', $product->id, null, $product->only(['sku', 'name', 'selling_price']));

        return back()->with('success', 'Product updated.');
    }

    public function toggle(Product $product)
    {
        $product->update(['status' => $product->isActive() ? Product::STATUS_INACTIVE : Product::STATUS_ACTIVE]);
        AuditLogger::log('product.toggled', 'product', $product->id);

        return back()->with('success', 'Product '.$product->status.'.');
    }
}
