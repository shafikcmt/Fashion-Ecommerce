<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\Variant;
use App\Notifications\OrderConfirmation;
use App\Support\ManualPayments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CheckoutService
{
    private const PLACEHOLDER_DOMAIN = '@phone.invalid';

    public function quote(Cart $cart, array $data): array
    {
        return DB::transaction(function () use ($cart, $data) {
            $locked = Cart::lockForUpdate()->findOrFail($cart->id);

            return $this->publicQuote($this->prepare($locked, $data));
        }, 5);
    }

    public function place(Cart $cart, array $data, string $key, ?string $expectedQuote = null): Order
    {
        return DB::transaction(function () use ($cart, $data, $key, $expectedQuote) {
            $locked = Cart::lockForUpdate()->findOrFail($cart->id);
            $prior = Order::where('checkout_key', $key)->first();
            if ($prior) {
                if ($prior->email !== self::contactEmail($data) || $prior->user_id !== $locked->user_id) {
                    abort(403);
                }

                return $prior;
            }
            $prepared = $this->prepare($locked, $data);
            $quote = $this->publicQuote($prepared);
            if ($expectedQuote !== null && ! hash_equals($expectedQuote, self::fingerprint($quote))) {
                $this->fail('cart', 'Your bag, prices or shipping changed. Review checkout again before placing your order.');
            }
            foreach ($prepared['items'] as $item) {
                $variant = $prepared['variants']->get($item['variant_id']);
                $variant->forceFill(['reserved' => $variant->reserved + $item['quantity']])->save();
            }
            $prepared['coupon']?->increment('uses');
            $order = Order::create([
                'public_id' => (string) Str::uuid(), 'checkout_key' => $key, 'user_id' => $locked->user_id, 'coupon_id' => $prepared['coupon']?->id,
                'customer_name' => $data['name'], 'email' => self::contactEmail($data), 'phone' => $data['phone'],
                'address' => ['address' => $data['address'], 'area' => $data['area'] ?? null, 'district' => $data['district'], 'postcode' => $data['postcode'] ?? null],
                'payment_method' => $data['payment_method'] ?? 'cod', 'payment_sender' => $this->walletOnly($data, 'payment_sender'), 'payment_reference' => ($ref = $this->walletOnly($data, 'payment_reference')) ? strtoupper(trim($ref)) : null,
                'shipping_method' => $prepared['zone']->name, 'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'],
                'shipping' => $quote['shipping'], 'total' => $quote['total'], 'reservation_expires_at' => ManualPayments::isManual($data['payment_method'] ?? 'cod') ? now()->addDays(3) : now()->addDay(),
            ]);
            $order->items()->createMany($prepared['items']);
            $locked->items()->delete();
            if (! self::isPlaceholderEmail($order->email)) {
                DB::afterCommit(fn () => Notification::route('mail', $order->email)->notify(new OrderConfirmation($order->id)));
            }

            return $order;
        }, 5);
    }

    /**
     * Phone-first checkout: when no email is given, the customer is identified by a placeholder
     * address derived from the normalized phone number so coupon limits and retries still work.
     */
    public static function contactEmail(array $data): string
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        return $email !== '' ? $email : 'phone-'.self::normalizePhone((string) $data['phone']).self::PLACEHOLDER_DOMAIN;
    }

    public static function isPlaceholderEmail(string $email): bool
    {
        return str_ends_with($email, self::PLACEHOLDER_DOMAIN);
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/^(?:\+?88)/', '', preg_replace('/[\s-]+/', '', $phone));
    }

    public static function fingerprint(array $quote): string
    {
        return hash('sha256', json_encode($quote, JSON_THROW_ON_ERROR));
    }

    private function prepare(Cart $cart, array $data): array
    {
        $method = $data['payment_method'] ?? 'cod';
        if ($method !== 'cod') {
            if (! array_key_exists($method, ManualPayments::available())) {
                $this->fail('payment_method', 'This payment method is not available.');
            }
            $reference = strtoupper(trim((string) ($data['payment_reference'] ?? '')));
            if (! preg_match('/^[A-Z0-9]{6,20}$/', $reference) || ! preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', (string) ($data['payment_sender'] ?? ''))) {
                $this->fail('payment_reference', 'Enter the sender number and the transaction ID of your payment.');
            }
            if (Order::where('payment_reference', $reference)->exists()) {
                $this->fail('payment_reference', 'This transaction ID has already been used.');
            }
        }
        $items = $cart->items()->orderBy('variant_id')->get();
        if ($items->isEmpty()) {
            $this->fail('cart', 'Your bag is empty.');
        }
        $zone = ShippingZone::where('active', true)->find($data['shipping_zone_id']);
        if (! $zone || (! in_array('*', $zone->districts, true) && ! in_array($data['district'], $zone->districts, true))) {
            $this->fail('shipping_zone_id', 'Choose a shipping method covering your district.');
        }
        $coupon = null;
        if (! empty($data['coupon'])) {
            $coupon = Coupon::where('code', strtoupper(trim($data['coupon'])))->lockForUpdate()->first();
            $uses = $coupon ? Order::where('coupon_id', $coupon->id)->where('email', self::contactEmail($data))->where('status', '!=', 'cancelled')->count() : 0;
            if (! $coupon || ! $coupon->active || $coupon->expires_at->isPast() || $coupon->uses >= $coupon->usage_limit || $uses >= $coupon->per_customer_limit) {
                $this->fail('coupon', 'This coupon is unavailable or its usage limit has been reached.');
            }
        }
        $variants = Variant::with('product')->whereIn('id', $items->pluck('variant_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $subtotal = 0;
        $eligible = 0;
        $snapshots = [];
        foreach ($items as $item) {
            $v = $variants->get($item->variant_id);
            if (! $v || $v->product->status !== 'published' || $item->quantity < 1 || $item->quantity > 99 || $v->available() < $item->quantity) {
                $this->fail('cart', 'An item is no longer available. Update your bag and try again.');
            }
            $line = $v->price() * $item->quantity;
            $subtotal += $line;
            if (! $coupon?->category_id || $coupon->category_id === $v->product->category_id) {
                $eligible += $line;
            }
            $snapshots[] = ['variant_id' => $v->id, 'product_name' => $v->product->name, 'sku' => $v->sku, 'size' => $v->size, 'color' => $v->color, 'unit_price' => $v->price(), 'quantity' => $item->quantity, 'line_total' => $line];
        }
        $discount = 0;
        if ($coupon) {
            if ($subtotal < $coupon->minimum || $eligible === 0) {
                $this->fail('coupon', 'Your bag does not meet this coupon’s eligibility requirements.');
            }
            $discount = min($eligible, $coupon->type === 'percent' ? intdiv($eligible * $coupon->value, 100) : $coupon->value);
        }

        $shipping = $this->shippingCharge($zone, $subtotal - $discount);

        return ['subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'total' => $subtotal - $discount + $shipping, 'items' => $snapshots, 'zone' => $zone, 'coupon' => $coupon, 'variants' => $variants];
    }

    /** Delivery is free once the discounted subtotal reaches the free_delivery_min setting (whole taka). */
    private function shippingCharge(ShippingZone $zone, int $payable): int
    {
        $threshold = (int) Setting::value('free_delivery_min', '0') * 100;

        return $threshold > 0 && $payable >= $threshold ? 0 : $zone->charge;
    }

    private function walletOnly(array $data, string $key): ?string
    {
        return ManualPayments::isManual($data['payment_method'] ?? 'cod') ? ($data[$key] ?? null) : null;
    }

    private function publicQuote(array $prepared): array
    {
        return array_intersect_key($prepared, array_flip(['subtotal', 'discount', 'shipping', 'total', 'items']));
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
