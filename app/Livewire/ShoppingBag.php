<?php

namespace App\Livewire;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShoppingBag extends Component
{
    public function setQuantity(int $variantId, int $quantity): void
    {
        session()->forget(['checkout_key', 'reviewed_checkout']);
        app(CartService::class)->set(app(CartService::class)->current(), $variantId, $quantity);
    }

    public function render(): View
    {
        $cart = app(CartService::class)->current()->load('items.variant.product.media');
        $subtotal = $cart->items->sum(fn ($i) => $i->variant->price() * $i->quantity);

        return view('livewire.shopping-bag', compact('cart', 'subtotal'))->layout('components.layouts.app', ['title' => __('Shopping bag')]);
    }
}
