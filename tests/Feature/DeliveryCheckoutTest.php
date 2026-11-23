<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\Variant;
use App\Notifications\OrderConfirmation;
use App\Services\CheckoutService;
use App\Support\BdDistricts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliveryCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
    }

    private function cartWithZones(int $price = 100000): array
    {
        $variant = Variant::factory()->create(['price' => $price]);
        $cart = Cart::create(['token' => (string) Str::uuid()]);
        $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
        $dhaka = ShippingZone::create(['name' => 'Inside Dhaka', 'districts' => ['Dhaka'], 'charge' => 7000]);
        $rest = ShippingZone::create(['name' => 'Outside Dhaka', 'districts' => ['*'], 'charge' => 13000]);
        $key = (string) Str::uuid();
        $this->withSession(['cart_token' => $cart->token, 'checkout_key' => $key]);

        return [$cart, $dhaka, $rest, ['name' => 'Buyer', 'phone' => '01712345678', 'address' => 'Test Road', 'area' => 'Mirpur', 'district' => 'Dhaka', 'shipping_zone_id' => $dhaka->id, 'payment_method' => 'cod', 'checkout_key' => $key, 'terms' => '1']];
    }

    public function test_there_are_exactly_64_bangladesh_districts_with_bangla_names(): void
    {
        $this->assertCount(64, BdDistricts::names());
        $this->assertSame('ঢাকা', BdDistricts::all()['Dhaka']);
        $this->assertNotContains('Dhaka', BdDistricts::outsideDhakaCity());
        $this->assertContains('Gazipur', BdDistricts::outsideDhakaCity());
    }

    public function test_checkout_form_offers_a_district_dropdown_and_optional_email(): void
    {
        $this->cartWithZones();
        $this->get(route('checkout'))->assertOk()->assertSee('name="district"', false)->assertSee('Cox&#039;s Bazar', false)->assertSee('name="area"', false)->assertSee('optional');
    }

    public function test_guest_can_order_with_phone_only_and_no_email_is_sent(): void
    {
        [$cart, , , $data] = $this->cartWithZones();
        $this->post(route('checkout.review'), $data)->assertOk();
        $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key']])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('phone-01712345678@phone.invalid', $order->email);
        $this->assertSame('Mirpur', $order->address['area']);
        Notification::assertNothingSent();
    }

    public function test_real_email_still_receives_the_confirmation(): void
    {
        [, , , $data] = $this->cartWithZones();
        $data['email'] = 'Buyer@Example.test';
        $this->post(route('checkout.review'), $data)->assertOk();
        $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key']])->assertRedirect();
        $this->assertSame('buyer@example.test', Order::firstOrFail()->email);
        Notification::assertSentOnDemand(OrderConfirmation::class);
    }

    public function test_phone_placeholder_identity_ignores_country_code_and_spacing(): void
    {
        $this->assertSame(CheckoutService::contactEmail(['phone' => '+880 1712-345678']), CheckoutService::contactEmail(['phone' => '01712345678']));
        $this->assertTrue(CheckoutService::isPlaceholderEmail('phone-01712345678@phone.invalid'));
        $this->assertFalse(CheckoutService::isPlaceholderEmail('buyer@example.test'));
    }

    public function test_unknown_district_is_rejected(): void
    {
        [, , , $data] = $this->cartWithZones();
        $data['district'] = 'Atlantis';
        $this->post(route('checkout.review'), $data)->assertSessionHasErrors('district');
    }

    public function test_zone_must_cover_the_chosen_district(): void
    {
        [, $dhaka, , $data] = $this->cartWithZones();
        $data['district'] = 'Sylhet';
        $data['shipping_zone_id'] = $dhaka->id;
        $this->post(route('checkout.review'), $data)->assertSessionHasErrors('shipping_zone_id');
    }

    public function test_delivery_is_free_from_the_configured_threshold_only(): void
    {
        Setting::create(['key' => 'free_delivery_min', 'value' => '3000']);
        [$cart, , $rest, $data] = $this->cartWithZones(200000);
        $data['district'] = 'Sylhet';
        $data['shipping_zone_id'] = $rest->id;
        $service = app(CheckoutService::class);
        $this->assertSame(13000, $service->quote($cart, $data)['shipping'], 'Below ৳3,000 the zone charge applies.');
        Variant::query()->update(['price' => 300000]);
        $quote = $service->quote($cart, $data);
        $this->assertSame(0, $quote['shipping']);
        $this->assertSame(300000, $quote['total']);
    }

    public function test_threshold_uses_the_discounted_subtotal(): void
    {
        Setting::create(['key' => 'free_delivery_min', 'value' => '3000']);
        [$cart, $dhaka, , $data] = $this->cartWithZones(310000);
        Coupon::create(['code' => 'TAKA500', 'type' => 'fixed', 'value' => 50000, 'minimum' => 0, 'usage_limit' => 10, 'per_customer_limit' => 1, 'expires_at' => now()->addDay(), 'active' => true]);
        $data['coupon'] = 'TAKA500';
        $this->assertSame(7000, app(CheckoutService::class)->quote($cart, $data)['shipping']);
    }
}
