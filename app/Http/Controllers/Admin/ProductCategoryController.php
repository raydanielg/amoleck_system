<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::withCount('products')->orderBy('sort_order')->orderBy('name')->paginate(25);

        return view('admin.supplies.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        ProductCategory::create($data + ['sort_order' => 0]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $productCategory->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function toggle(ProductCategory $productCategory)
    {
        $productCategory->update(['is_active' => ! $productCategory->is_active]);

        return back()->with('success', 'Category '.($productCategory->is_active ? 'activated' : 'deactivated').'.');
    }
}
