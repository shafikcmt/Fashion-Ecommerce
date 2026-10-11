<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProductPurchase extends Component
{
    #[Locked]
    public int $productId;

    public string $size = '';

    public string $color = '';

    public int $quantity = 1;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function add(): void
    {
        $this->validate(['size' => 'required|string', 'color' => 'required|string', 'quantity' => 'required|integer|min:1|max:99']);
        $product = Product::published()->findOrFail($this->productId);
        $v = $product->variants()->where('size', $this->size)->where('color', $this->color)->first();
        if (! $v) {
            $this->addError('size', __('This combination is unavailable.'));

            return;
        }
        $service = app(CartService::class);
        $cart = $service->current();
        $service->add($cart, $v->id, $this->quantity);
        session()->forget('checkout_key');
        session()->flash('added', __('Added to your bag.'));
    }

    public function render(): View
    {
        $product = Product::published()->with('variants.media')->findOrFail($this->productId);
        $variant = $product->variants->first(fn ($v) => $v->size === $this->size && $v->color === $this->color);

        return view('livewire.product-purchase', compact('product', 'variant'));
    }
}
