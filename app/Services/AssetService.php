<?php

namespace App\Services;

use App\Models\Asset;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetService
{
    public static function nextNumber(): string
    {
        return sprintf('AST-%d-%06d', now()->year, Asset::whereYear('created_at', now()->year)->count() + 1);
    }

    /** Current book value per configured depreciation. */
    public static function bookValue(Asset $asset): ?float
    {
        if (! $asset->purchase_cost || ! $asset->purchased_at || ! $asset->useful_life_years) {
            return $asset->purchase_cost ? (float) $asset->purchase_cost : null;
        }
        $years = max(0, $asset->purchased_at->diffInDays(now()) / 365.25);
        $residual = (float) $asset->residual_value;
        $cost = (float) $asset->purchase_cost;

        if ($asset->depreciation_method === 'declining') {
            $rate = 2 / $asset->useful_life_years;
            $value = $cost * pow(1 - $rate, $years);
            return round(max($value, $residual), 2);
        }
        // straight line
        $perYear = ($cost - $residual) / $asset->useful_life_years;
        return round(max($cost - $perYear * $years, $residual), 2);
    }

    public static function assign(Asset $asset, ?int $employeeId, ?int $branchId, ?string $condition, ?string $notes, int $userId): void
    {
        if ($asset->status === 'disposed') {
            throw ValidationException::withMessages(['asset' => 'Disposed assets cannot be assigned.']);
        }
        DB::transaction(function () use ($asset, $employeeId, $branchId, $condition, $notes, $userId) {
            $asset->assignments()->create([
                'employee_id' => $employeeId, 'branch_id' => $branchId, 'action' => 'assigned',
                'condition' => $condition, 'notes' => $notes, 'recorded_by' => $userId,
            ]);
            $asset->update(['employee_id' => $employeeId, 'branch_id' => $branchId ?? $asset->branch_id]);
        });
        AuditLogger::log('asset.assigned', 'asset', $asset->id);
    }

    public static function returnAsset(Asset $asset, ?string $condition, ?string $notes, int $userId): void
    {
        DB::transaction(function () use ($asset, $condition, $notes, $userId) {
            $asset->assignments()->create([
                'employee_id' => $asset->employee_id, 'branch_id' => $asset->branch_id, 'action' => 'returned',
                'condition' => $condition, 'notes' => $notes, 'recorded_by' => $userId,
            ]);
            $asset->update(['employee_id' => null, 'condition' => $condition ?? $asset->condition]);
        });
        AuditLogger::log('asset.returned', 'asset', $asset->id);
    }

    public static function dispose(Asset $asset, string $reason, ?float $value, int $userId): void
    {
        DB::transaction(function () use ($asset, $reason, $value, $userId) {
            $asset->assignments()->create([
                'employee_id' => $asset->employee_id, 'branch_id' => $asset->branch_id, 'action' => 'transferred',
                'condition' => $asset->condition, 'notes' => "Disposed: {$reason}", 'recorded_by' => $userId,
            ]);
            $asset->update(['status' => 'disposed', 'disposed_at' => now(), 'disposal_reason' => $reason, 'disposal_value' => $value, 'employee_id' => null]);
        });
        AuditLogger::log('asset.disposed', 'asset', $asset->id);
    }
}
