<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryService
{
    public function adjust(int $id, int $quantity, string $reason, ?int $actor = null): void
    {
        DB::transaction(function () use ($id, $quantity, $reason, $actor) {
            $variant = Variant::lockForUpdate()->findOrFail($id);
            if ($variant->stock + $quantity < $variant->reserved || $quantity === 0) {
                throw ValidationException::withMessages(['quantity' => 'Adjustment would affect reserved stock or has no effect.']);
            }
            $variant->forceFill(['stock' => $variant->stock + $quantity])->save();
            StockMovement::create(['variant_id' => $id, 'user_id' => $actor, 'quantity' => $quantity, 'reason' => $reason]);
        }, 5);
    }
}
