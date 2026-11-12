<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductFulfilment;
use App\Models\ServiceFulfilment;
use App\Services\AuditLogger;
use App\Services\FulfilmentService;
use Illuminate\Http\Request;

class FulfilmentController extends Controller
{
    /** Delivery tracking center — all product fulfilment orders. */
    public function index(Request $request)
    {
        $query = ProductFulfilment::with(['invoice', 'sale', 'patient', 'customer', 'assignee'])->latest();
        if ($s = $request->input('q')) {
            $query->where(fn ($q) => $q->where('tracking_reference', 'like', "%{$s}%")->orWhere('recipient_name', 'like', "%{$s}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        $fulfilments = $query->paginate(25)->withQueryString();

        return view('admin.supplies.deliveries.index', [
            'fulfilments' => $fulfilments,
            'steps' => ProductFulfilment::STEPS,
        ]);
    }

    public function serviceStep(Request $request, ServiceFulfilment $fulfilment)
    {
        $data = $request->validate([
            'step' => ['required', 'in:'.implode(',', ServiceFulfilment::STEPS)],
            'notes' => ['nullable', 'string', 'max:500'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'scheduled_date' => ['nullable', 'date'],
        ]);
        try {
            FulfilmentService::advanceService($fulfilment, $data['step'], $data['notes'] ?? null, $data['assigned_to'] ?? null, $data['scheduled_date'] ?? null, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLogger::log('fulfilment.service_step', 'service_fulfilment', $fulfilment->id, null, ['step' => $data['step']]);

        return back()->with('success', 'Service step updated.');
    }

    public function productStep(Request $request, ProductFulfilment $fulfilment)
    {
        $data = $request->validate([
            'step' => ['required', 'in:'.implode(',', ProductFulfilment::STEPS)],
            'notes' => ['nullable', 'string', 'max:500'],
            'delivery_method' => ['nullable', 'string', 'max:40'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_phone' => ['nullable', 'string', 'max:40'],
            'courier' => ['nullable', 'string', 'max:120'],
            'expected_delivery_date' => ['nullable', 'date'],
            'proof_note' => ['nullable', 'string', 'max:500'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);
        try {
            FulfilmentService::advanceProduct($fulfilment, $data['step'], $data['notes'] ?? null, $data, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        AuditLogger::log('fulfilment.product_step', 'product_fulfilment', $fulfilment->id, null, ['step' => $data['step']]);

        return back()->with('success', 'Order step updated.');
    }

    public function correct(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:service,product'],
            'id' => ['required', 'integer'],
            'step' => ['required', 'string', 'max:40'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        try {
            FulfilmentService::correct($data['type'], $data['id'], $data['step'], $data['reason'], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status corrected.');
    }
}
