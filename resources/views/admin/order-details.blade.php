<div style="display: grid; gap: 1.5rem; overflow-wrap: anywhere">
    <x-filament::section heading="Customer & delivery">
        <dl style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 1rem" data-review-private>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Customer</dt><dd style="font-weight: 500">{{ $order->customer_name }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Contact</dt><dd>{{ $order->email }}<br>{{ $order->phone }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Delivery address</dt><dd>{{ $order->address['address'] }}, {{ $order->address['district'] }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Shipping method</dt><dd>{{ $order->shipping_method }}</dd></div>
        </dl>
    </x-filament::section>
    <x-filament::section heading="Order items">
        <div style="display: grid; gap: 1rem">
            @foreach($order->items as $item)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 0.5rem">
                    <div><p style="font-weight: 500">{{ $item->product_name }}</p><p style="font-size: 0.875rem; opacity: 0.7">{{ $item->sku }} · {{ $item->size }} / {{ $item->color }}</p></div>
                    <p>{{ $item->quantity }} × {{ \App\Support\Money::format($item->unit_price) }} = {{ \App\Support\Money::format($item->line_total) }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
    <x-filament::section heading="Totals & status">
        <dl style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap: 1rem">
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Order / fulfillment</dt><dd>{{ ucfirst($order->status) }} / {{ ucfirst($order->fulfillment_status) }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Payment</dt><dd>{{ ucfirst($order->payment_status) }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Courier / tracking</dt><dd>{{ $order->courier ?: 'Unassigned' }} / {{ $order->tracking_number ?: 'Not assigned' }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Subtotal</dt><dd>{{ \App\Support\Money::format($order->subtotal) }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Discount</dt><dd>{{ \App\Support\Money::format($order->discount) }}</dd></div>
            <div><dt style="font-size: 0.875rem; opacity: 0.7">Shipping</dt><dd>{{ \App\Support\Money::format($order->shipping) }}</dd></div>
            <div><dt style="font-weight: 500">Order total</dt><dd style="font-weight: 600">{{ \App\Support\Money::format($order->total) }}</dd></div>
        </dl>
    </x-filament::section>
</div>
