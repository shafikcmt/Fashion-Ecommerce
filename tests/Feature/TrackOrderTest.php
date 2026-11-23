<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
    }

    private function placeOrder(): Order
    {
        $variant = Variant::factory()->create();
        $cart = Cart::create(['token' => (string) Str::uuid()]);
        $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
        $zone = ShippingZone::create(['name' => 'All', 'districts' => ['*'], 'charge' => 7000]);
        $key = (string) Str::uuid();
        $this->withSession(['cart_token' => $cart->token, 'checkout_key' => $key]);
        $data = ['name' => 'Buyer', 'phone' => '01712345678', 'address' => 'Road 1', 'district' => 'Dhaka', 'shipping_zone_id' => $zone->id, 'payment_method' => 'cod', 'checkout_key' => $key, 'terms' => '1'];
        $this->post(route('checkout.review'), $data)->assertOk();
        $this->post(route('checkout.place'), ['checkout_key' => $key])->assertRedirect();

        return Order::firstOrFail();
    }

    public function test_form_renders(): void
    {
        $this->get(route('track'))->assertOk()->assertSee('name="order_number"', false);
    }

    public function test_matching_number_and_phone_opens_the_order_even_with_country_code(): void
    {
        $order = $this->placeOrder();
        $this->flushSession();
        $response = $this->post(route('track.lookup'), ['order_number' => $order->id, 'phone' => '+880 1712-345678'])->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Buyer');
    }

    public function test_wrong_phone_or_number_gets_the_same_generic_error(): void
    {
        $order = $this->placeOrder();
        $this->flushSession();
        $this->post(route('track.lookup'), ['order_number' => $order->id, 'phone' => '01999999999'])->assertSessionHasErrors('order_number');
        $this->post(route('track.lookup'), ['order_number' => $order->id + 99, 'phone' => '01712345678'])->assertSessionHasErrors('order_number');
    }
}
