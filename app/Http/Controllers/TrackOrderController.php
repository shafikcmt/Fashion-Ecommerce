<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class TrackOrderController extends Controller
{
    public function form(): View
    {
        return view('store.track');
    }

    /**
     * Look an order up by its number and the phone number used at checkout, then open its signed page.
     * The same message is returned for a wrong number or wrong phone so orders cannot be enumerated.
     */
    public function lookup(Request $r): RedirectResponse
    {
        $data = $r->validate(['order_number' => 'required|integer|min:1', 'phone' => 'required|string|max:30']);
        $order = Order::find($data['order_number']);
        if (! $order || CheckoutService::normalizePhone($order->phone) !== CheckoutService::normalizePhone($data['phone'])) {
            throw ValidationException::withMessages(['order_number' => __('We could not find an order with this number and phone.')]);
        }

        return redirect(URL::temporarySignedRoute('orders.show', now()->addHour(), ['order' => $order->public_id]));
    }
}
