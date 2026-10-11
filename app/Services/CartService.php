<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CartService
{
    public function current(): Cart
    {
        $token = session('cart_token');
        if (! $token) {
            $token = (string) Str::uuid();
            session(['cart_token' => $token]);
        }
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()], ['token' => (string) Str::uuid()]);
        }

        return Cart::firstOrCreate(['token' => $token], ['user_id' => null]);
    }

    public function add(Cart $cart, int $variantId, int $quantity): void
    {
        DB::transaction(function () use ($cart, $variantId, $quantity) {
            Cart::lockForUpdate()->findOrFail($cart->id);
            $current = (int) ($cart->items()->where('variant_id', $variantId)->value('quantity') ?? 0);
            $this->set($cart, $variantId, $current + $quantity);
        }, 5);
    }

    public function set(Cart $cart, int $variantId, int $quantity): void
    {
        DB::transaction(function () use ($cart, $variantId, $quantity) {
            Cart::lockForUpdate()->findOrFail($cart->id);
            $variant = Variant::with('product')->findOrFail($variantId);
            if ($quantity < 0 || $quantity > 99 || ($quantity > 0 && ($variant->product->status !== 'published' || $quantity > $variant->available()))) {
                throw ValidationException::withMessages(['quantity' => 'That quantity is unavailable. Choose up to 99 available items.']);
            }
            if ($quantity === 0) {
                $cart->items()->where('variant_id', $variantId)->delete();
            } else {
                $cart->items()->updateOrCreate(['variant_id' => $variantId], ['quantity' => $quantity]);
            }
        }, 5);
    }

    public function merge(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $guest = Cart::where('token', session('cart_token'))->whereNull('user_id')->lockForUpdate()->first();
            $owned = Cart::firstOrCreate(['user_id' => $userId], ['token' => (string) Str::uuid()]);
            $owned = Cart::lockForUpdate()->findOrFail($owned->id);
            if (! $guest || $guest->id === $owned->id) {
                return;
            }
            foreach ($guest->items()->with('variant.product')->get() as $item) {
                if ($item->variant->product->status !== 'published') {
                    continue;
                }
                $existing = $owned->items()->where('variant_id', $item->variant_id)->first();
                $qty = min(99, $item->variant->available(), $item->quantity + ($existing?->quantity ?? 0));
                if ($qty > 0) {
                    $owned->items()->updateOrCreate(['variant_id' => $item->variant_id], ['quantity' => $qty]);
                }
            }
            $guest->delete();
            session()->forget('cart_token');
        }, 5);
    }
}
