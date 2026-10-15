<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\StockMovement;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderService
{
    public function transition(Order $order, string $next): Order
    {
        return DB::transaction(function () use ($order, $next) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            $allowed = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['shipped', 'cancelled'], 'shipped' => ['delivered'], 'delivered' => [], 'cancelled' => []];
            if (! in_array($next, $allowed[$o->status] ?? [], true)) {
                $this->fail('Invalid order status transition.');
            }
            if ($next === 'confirmed' && $o->reservation_expires_at?->isPast()) {
                $this->fail('This reservation has expired. Cancel the order.');
            }
            if (in_array($next, ['cancelled', 'shipped'], true)) {
                if ($next === 'cancelled' && $o->coupon_id) {
                    Coupon::whereKey($o->coupon_id)->lockForUpdate()->first()?->decrement('uses');
                }
                foreach ($o->items()->orderBy('variant_id')->get() as $item) {
                    $v = Variant::lockForUpdate()->findOrFail($item->variant_id);
                    $changes = ['reserved' => $v->reserved - $item->quantity];
                    if ($next === 'shipped') {
                        $changes['stock'] = $v->stock - $item->quantity;
                        StockMovement::create(['variant_id' => $v->id, 'user_id' => auth()->id(), 'quantity' => -$item->quantity, 'reason' => 'Shipped order '.$o->id]);
                    }
                    $v->forceFill($changes)->save();
                }
            }
            $changes = ['status' => $next];
            if ($next === 'confirmed') {
                $changes['reservation_expires_at'] = null;
            }
            if ($next === 'cancelled') {
                $changes += ['reservation_expires_at' => null, 'fulfillment_status' => 'cancelled'];
            }
            if ($next === 'shipped') {
                $changes += ['stock_consumed_at' => now(), 'reservation_expires_at' => null, 'fulfillment_status' => 'shipped'];
            }
            if ($next === 'delivered') {
                $changes['fulfillment_status'] = 'delivered';
            }
            $o->update($changes);

            return $o;
        }, 5);
    }

    public function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->status !== 'pending' || ! $o->reservation_expires_at || $o->reservation_expires_at->isFuture()) {
                return false;
            }
            $this->transition($o, 'cancelled');

            return true;
        }, 5);
    }

    public function cancelPending(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->status !== 'pending') {
                $this->fail('Contact the store to cancel a confirmed order.');
            }
            $this->transition($o, 'cancelled');
        }, 5);
    }

    public function collectCod(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->payment_method !== 'cod' || $o->status !== 'delivered' || $o->payment_status !== 'unpaid') {
                $this->fail('COD may only be collected once after delivery.');
            }
            $o->update(['payment_status' => 'paid']);
        }, 5);
    }

    public function requestReturn(Order $order, string $reason): ReturnRequest
    {
        return DB::transaction(function () use ($order, $reason) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->status !== 'delivered' || $o->returnRequest()->exists()) {
                $this->fail('A return can be requested once after delivery.');
            }

            return $o->returnRequest()->create(['reason' => $reason]);
        }, 5);
    }

    public function reviewReturn(ReturnRequest $request, string $decision, string $note): void
    {
        DB::transaction(function () use ($request, $decision, $note) {
            $o = Order::lockForUpdate()->findOrFail($request->order_id);
            $r = ReturnRequest::lockForUpdate()->findOrFail($request->id);
            if ($r->status !== 'requested' || ! in_array($decision, ['approved', 'rejected'], true)) {
                $this->fail('This return has already been reviewed.');
            }
            $r->update(['status' => $decision, 'admin_note' => $note]);
        }, 5);
    }

    public function restockReturn(ReturnRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $o = Order::lockForUpdate()->findOrFail($request->order_id);
            $r = ReturnRequest::lockForUpdate()->findOrFail($request->id);
            if ($r->status !== 'approved' || $r->restocked_at || ! $o->stock_consumed_at) {
                $this->fail('Only approved, received returns may be restocked once.');
            }
            foreach ($o->items()->orderBy('variant_id')->get() as $item) {
                app(InventoryService::class)->adjust($item->variant_id, $item->quantity, 'Approved return '.$r->id, auth()->id());
            }
            $r->update(['status' => 'received', 'restocked_at' => now()]);
            $o->update(['fulfillment_status' => 'returned']);
        }, 5);
    }

    public function recordRefund(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->payment_status !== 'paid' || $o->returnRequest?->status !== 'received') {
                $this->fail('Record a refund after a paid order’s return is received.');
            }
            $o->update(['payment_status' => 'refunded']);
        }, 5);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['status' => $message]);
    }
}
