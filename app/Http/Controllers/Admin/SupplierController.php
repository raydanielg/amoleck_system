<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount(['purchases'])->latest()->paginate(25);

        return view('admin.supplies.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        Supplier::create($request->validate([
            'name' => 'required|string|max:160',
            'contact_person' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'address' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:160',
            'notes' => 'nullable|string|max:1000',
        ]));

        return back()->with('success', 'Supplier created.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate([
            'name' => 'required|string|max:160',
            'contact_person' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'address' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:160',
            'notes' => 'nullable|string|max:1000',
        ]));

        return back()->with('success', 'Supplier updated.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return back()->with('success', 'Supplier '.($supplier->is_active ? 'activated' : 'deactivated').'.');
    }
}
