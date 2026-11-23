<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Support\BdDistricts;
use App\Support\ManualPayments;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function form(): View
    {
        if (! session('checkout_key')) {
            session(['checkout_key' => (string) Str::uuid()]);
        }

        return view('store.checkout', ['cart' => app(CartService::class)->current()->load('items.variant.product'), 'zones' => ShippingZone::where('active', true)->get()]);
    }

    public function review(Request $r): View
    {
        $data = $r->validate([
            'name' => 'required|string|max:100', 'email' => 'nullable|email|max:255', 'phone' => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'address' => 'required|string|max:255', 'area' => 'nullable|string|max:150', 'district' => ['required', Rule::in(BdDistricts::names())], 'postcode' => 'nullable|string|max:20',
            'shipping_zone_id' => 'required|integer', 'coupon' => 'nullable|string|max:50', 'payment_method' => ['required', Rule::in(['cod', ...array_keys(ManualPayments::available())])],
            'payment_sender' => ['nullable', 'required_unless:payment_method,cod', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'], 'payment_reference' => ['nullable', 'required_unless:payment_method,cod', 'alpha_num', 'between:6,20'],
            'checkout_key' => 'required|uuid', 'terms' => 'accepted',
        ]);
        abort_unless(hash_equals((string) session('checkout_key'), $data['checkout_key']), 419);
        $quote = app(CheckoutService::class)->quote(app(CartService::class)->current(), $data);
        session(['reviewed_checkout' => ['data' => $data, 'fingerprint' => CheckoutService::fingerprint($quote)]]);

        return view('store.review', compact('data', 'quote'));
    }

    public function place(Request $r): RedirectResponse
    {
        $r->validate(['checkout_key' => 'required|uuid']);
        $review = session('reviewed_checkout');
        abort_unless($review && hash_equals((string) session('checkout_key'), $r->checkout_key) && $review['data']['checkout_key'] === $r->checkout_key, 419);
        try {
            $order = app(CheckoutService::class)->place(app(CartService::class)->current(), $review['data'], $r->checkout_key, $review['fingerprint']);
        } catch (ValidationException $e) {
            return redirect()->route('checkout')->withErrors($e->errors())->withInput($review['data']);
        }
        session()->push('guest_orders', $order->public_id);
        session()->flash('purchase', ['id' => $order->id, 'value' => round($order->total / 100, 2)]);

        return redirect(URL::temporarySignedRoute('orders.show', now()->addDays(30), ['order' => $order->public_id]));
    }

    public function show(Request $r, Order $order): View
    {
        $this->authorizeView($r, $order);
        $order->load(['items', 'returnRequest']);

        return view('store.order', compact('order'));
    }

    public function invoice(Request $r, Order $order): View
    {
        $this->authorizeView($r, $order);

        return view('store.invoice', ['order' => $order->load('items')]);
    }

    public function cancel(Request $r, Order $order): RedirectResponse
    {
        $this->authorizeView($r, $order);
        abort_unless($order->status === 'pending' && $order->payment_method === 'cod', 422, 'Contact the store to cancel this order.');
        app(OrderService::class)->cancelPending($order);

        return back()->with('status', __('Order cancelled.'));
    }

    public function requestReturn(Request $r, Order $order): RedirectResponse
    {
        $this->authorizeView($r, $order);
        $data = $r->validate(['reason' => 'required|string|min:10|max:2000']);
        app(OrderService::class)->requestReturn($order, $data['reason']);

        return back()->with('status', __('Return requested.'));
    }

    private function authorizeView(Request $r, Order $order): void
    {
        abort_unless(($r->user() && $r->user()->can('view', $order)) || in_array($order->public_id, session('guest_orders', []), true) || $r->hasValidSignature(), 403);
    }
}
