<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::with('category')->orderBy('sort_order')->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId = $request->query('category')) {
            $query->where('service_category_id', $categoryId);
        }

        return view('admin.services.index', [
            'services' => $query->paginate(20)->withQueryString(),
            'categories' => ServiceCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.create', ['categories' => ServiceCategory::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateService($request);

        $service = Service::create([...$validated, 'created_by' => $request->user()->id]);

        AuditLogger::log('service.created', 'service', $service->id, null, $service->only(['name', 'service_code', 'standard_price']));

        return redirect()->route('admin.services.index')->with('success', 'Service created.');
    }

    public function show(Service $service): View
    {
        $usageCount = \App\Models\VisitService::where('service_id', $service->id)
            ->whereHas('visit', fn ($v) => $v->where('status', '!=', \App\Models\Visit::STATUS_VOIDED))
            ->count();
        $billedRevenue = \App\Models\InvoiceItem::where('service_id', $service->id)
            ->whereHas('invoice', fn ($i) => $i->where('status', '!=', \App\Models\Invoice::STATUS_VOIDED))
            ->sum('line_total');

        return view('admin.services.show', [
            'service' => $service->load('category', 'priceHistories.changer'),
            'usageCount' => $usageCount,
            'billedRevenue' => $billedRevenue,
            'recentUsage' => \App\Models\VisitService::with('visit.patient')
                ->where('service_id', $service->id)
                ->whereHas('visit', fn ($v) => $v->where('status', '!=', \App\Models\Visit::STATUS_VOIDED))
                ->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Service $service): View
    {
        return view('admin.services.edit', [
            'service' => $service,
            'categories' => ServiceCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $this->validateService($request, $service);
        $old = $service->only(array_keys($validated));

        $priceChanged = array_key_exists('standard_price', $validated)
            && bccomp((string) $validated['standard_price'], (string) $service->standard_price, 2) !== 0;

        $service->update($validated);

        if ($priceChanged) {
            \App\Models\ServicePriceHistory::create([
                'service_id' => $service->id,
                'old_price' => $old['standard_price'] ?? '0.00',
                'new_price' => $validated['standard_price'],
                'changed_by' => $request->user()->id,
            ]);
        }

        AuditLogger::log('service.updated', 'service', $service->id, $old, $service->only(array_keys($validated)));

        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        AuditLogger::log($service->is_active ? 'service.activated' : 'service.deactivated', 'service', $service->id);

        return back()->with('success', $service->is_active ? 'Service activated.' : 'Service deactivated.');
    }

    private function validateService(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_code' => ['nullable', 'string', 'max:50', Rule::unique('services')->ignore($service)],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'standard_price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'requires_notes' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
