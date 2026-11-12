<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with('category', 'branch', 'employee')
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%$v%")->orWhere('asset_number', 'like', "%$v%")->orWhere('serial_number', 'like', "%$v%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->category, fn ($q, $v) => $q->where('asset_category_id', $v))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.assets.index', [
            'assets' => $assets,
            'categories' => AssetCategory::where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::where('status', 'active')->get(),
            'employees' => Employee::where('status', 'active')->orderBy('full_name')->get(),
            'stats' => [
                'total' => Asset::count(),
                'value' => Asset::sum('purchase_cost'),
                'assigned' => Asset::whereNotNull('employee_id')->count(),
                'maintenance' => Asset::where('status', 'maintenance')->count(),
                'warranty_expiring' => Asset::whereNotNull('warranty_expires')->where('warranty_expires', '<=', now()->addDays(60))->where('status', '!=', 'disposed')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200', 'asset_category_id' => 'nullable|exists:asset_categories,id',
            'serial_number' => 'nullable|string|max:100', 'model' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100', 'purchased_at' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0', 'supplier_name' => 'nullable|string|max:150',
            'warranty_expires' => 'nullable|date', 'branch_id' => 'nullable|exists:branches,id',
            'condition' => 'required|in:new,good,fair,poor',
            'useful_life_years' => 'nullable|integer|min:1|max:50', 'residual_value' => 'nullable|numeric|min:0',
            'depreciation_method' => 'required|in:straight_line,declining',
        ]);
        $data['asset_number'] = AssetService::nextNumber();
        $asset = Asset::create($data);
        \App\Services\AuditLogger::log('asset.created', 'asset', $asset->id);
        return back()->with('success', "Asset {$asset->asset_number} registered.");
    }

    public function show(Asset $asset)
    {
        $asset->load('category', 'branch', 'employee', 'assignments.employee', 'assignments.recorder', 'maintenance.recorder');
        return view('admin.assets.show', ['asset' => $asset, 'bookValue' => AssetService::bookValue($asset), 'employees' => Employee::where('status', 'active')->get(), 'branches' => Branch::where('status', 'active')->get()]);
    }

    public function assign(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'employee_id' => 'nullable|exists:employees,id', 'branch_id' => 'nullable|exists:branches,id',
            'condition' => 'nullable|in:new,good,fair,poor', 'notes' => 'nullable|string|max:500',
        ]);
        AssetService::assign($asset, $data['employee_id'], $data['branch_id'] ?? null, $data['condition'] ?? null, $data['notes'] ?? null, auth()->id());
        return back()->with('success', 'Asset assigned.');
    }

    public function returnAsset(Request $request, Asset $asset)
    {
        $data = $request->validate(['condition' => 'nullable|in:new,good,fair,poor', 'notes' => 'nullable|string|max:500']);
        AssetService::returnAsset($asset, $data['condition'] ?? null, $data['notes'] ?? null, auth()->id());
        return back()->with('success', 'Asset returned.');
    }

    public function maintenance(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'type' => 'required|in:repair,service,inspection', 'provider' => 'nullable|string|max:150',
            'performed_at' => 'nullable|date', 'next_due' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0', 'notes' => 'nullable|string|max:500',
        ]);
        $asset->maintenance()->create($data + ['recorded_by' => auth()->id()]);
        return back()->with('success', 'Maintenance recorded.');
    }

    public function dispose(Request $request, Asset $asset)
    {
        $data = $request->validate(['reason' => 'required|string|max:500', 'disposal_value' => 'nullable|numeric|min:0']);
        AssetService::dispose($asset, $data['reason'], isset($data['disposal_value']) ? (float) $data['disposal_value'] : null, auth()->id());
        return back()->with('success', 'Asset disposed.');
    }

    public function storeCategory(Request $request)
    {
        AssetCategory::create($request->validate(['name' => 'required|string|max:100|unique:asset_categories,name']));
        return back()->with('success', 'Asset category added.');
    }
}
