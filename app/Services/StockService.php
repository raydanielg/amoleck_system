<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * Apply a signed stock change with a mandatory movement record.
     * Positive = stock in, negative = stock out.
     */
    public function move(Product $product, int $quantity, string $type, ?string $reference, ?string $reason, int $userId): StockMovement
    {
        return DB::transaction(function () use ($product, $quantity, $type, $reference, $reason, $userId) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->first();
            $newQty = $locked->stock_quantity + $quantity;

            if ($newQty < 0) {
                throw new RuntimeException("Insufficient stock for {$locked->name}. Available: {$locked->stock_quantity}.");
            }

            $locked->update(['stock_quantity' => $newQty]);
            $product->stock_quantity = $newQty;

            return StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_after' => $newQty,
                'reference' => $reference,
                'reason' => $reason,
                'user_id' => $userId,
            ]);
        });
    }
}
