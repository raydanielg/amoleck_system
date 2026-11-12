<?php

namespace App\Services;

use App\Models\InventoryBatch;
use Illuminate\Support\Facades\DB;

class InventoryBatchService
{
    public static function nextBatchNumber(int $productId): string
    {
        return sprintf('B-%d-%04d', $productId, InventoryBatch::where('product_id', $productId)->count() + 1);
    }

    /** FEFO allocation: earliest-expiring active batch first. Returns [batch_id => qty]. */
    public static function allocate(int $productId, int $qty): array
    {
        $batches = InventoryBatch::where('product_id', $productId)
            ->where('status', 'active')
            ->where('qty_available', '>', 0)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()->toDateString()))
            ->orderByRaw('expires_at IS NULL, expires_at ASC')
            ->orderBy('received_at')
            ->get();

        $remaining = $qty;
        $allocation = [];
        foreach ($batches as $b) {
            if ($remaining <= 0) break;
            $take = min($b->qty_available, $remaining);
            $allocation[$b->id] = $take;
            $remaining -= $take;
        }
        if ($remaining > 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['qty' => 'Insufficient valid batch stock (short '.$remaining.').']);
        }
        return $allocation;
    }

    /** Deduct quantities after allocate(). */
    public static function deduct(array $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            foreach ($allocation as $batchId => $qty) {
                $batch = InventoryBatch::lockForUpdate()->findOrFail($batchId);
                $batch->decrement('qty_available', $qty);
                if ($batch->fresh()->qty_available <= 0) $batch->update(['status' => 'depleted']);
            }
        });
    }

    /** Refresh expired statuses; return counts for dashboards. */
    public static function expirySummary(): array
    {
        InventoryBatch::where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now())->update(['status' => 'expired']);
        $days = (int) \App\Support\Settings::int('expiry_warning_days', 30);
        return [
            'expired' => InventoryBatch::where('status', 'expired')->where('qty_available', '>', 0)->count(),
            'expiring' => InventoryBatch::where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays($days))->where('qty_available', '>', 0)->count(),
            'quarantined' => InventoryBatch::where('status', 'quarantined')->count(),
        ];
    }
}
