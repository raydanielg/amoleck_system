<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('sales')->latest()->paginate(25);

        return view('admin.supplies.customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $customer = Customer::create($request->validate([
            'name' => 'required|string|max:160',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]));

        if ($request->wantsJson()) {
            return response()->json($customer);
        }

        return back()->with('success', 'Customer created.');
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->validate([
            'name' => 'required|string|max:160',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]));

        return back()->with('success', 'Customer updated.');
    }
}
