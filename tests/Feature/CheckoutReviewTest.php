<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Models\Variant;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
    }

    private function setupCart(): array
    {
        $v = Variant::factory()->create(['price' => 12345]);
        $cart = Cart::create(['token' => (string) Str::uuid()]);
        $cart->items()->create(['variant_id' => $v->id, 'quantity' => 2]);
        $zone = ShippingZone::create(['name' => 'Dhaka', 'districts' => ['Dhaka'], 'charge' => 8000]);
        $key = (string) Str::uuid();
        $this->withSession(['cart_token' => $cart->token, 'checkout_key' => $key]);

        return [$v, $cart, ['name' => 'Buyer', 'email' => 'buyer@example.test', 'phone' => '01712345678', 'address' => 'Test Road', 'district' => 'Dhaka', 'shipping_zone_id' => $zone->id, 'payment_method' => 'cod', 'checkout_key' => $key, 'terms' => '1']];
    }

    public function test_review_shows_exact_total_then_places_once(): void
    {
        [$v,$cart,$data] = $this->setupCart();
        $this->post(route('checkout.review'), $data)->assertOk()->assertSee('৳326.90');
        $this->assertSame(0, $v->fresh()->reserved);
        $response = $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key'], 'total' => 1]);
        $response->assertRedirect();
        $this->assertSame(32690, (int) Order::first()->total);
        $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key']])->assertRedirect();
        $this->assertSame(1, Order::count());
        $this->assertSame(2, $v->fresh()->reserved);
        $this->get($response->headers->get('Location'))->assertOk();
    }

    public function test_price_change_after_review_requires_review_again_and_does_not_reserve(): void
    {
        [$v,$cart,$data] = $this->setupCart();
        $this->post(route('checkout.review'), $data)->assertOk();
        $v->update(['price' => 15000]);
        $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key']])->assertRedirect(route('checkout'))->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $v->fresh()->reserved);
    }

    public function test_post_without_a_review_is_rejected(): void
    {
        [$v,$cart,$data] = $this->setupCart();
        $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key']])->assertStatus(419);
    }

    public function test_terms_are_required_by_server(): void
    {
        [$v,$cart,$data] = $this->setupCart();
        unset($data['terms']);
        $this->post(route('checkout.review'), $data)->assertSessionHasErrors('terms');
    }

    public function test_money_symbol_and_bangla_are_preserved_as_unicode(): void
    {
        $this->assertSame('৳123.45', Money::format(12345));
        $this->withSession(['locale' => 'bn'])->get('/shop')->assertOk()->assertSee('খুঁজুন')->assertSee('সব পোশাক');
    }

    public function test_expiry_does_not_cancel_a_confirmed_order(): void
    {
        [$v,$cart,$data] = $this->setupCart();
        $o = app(CheckoutService::class)->place($cart, $data, $data['checkout_key']);
        app(OrderService::class)->transition($o, 'confirmed');
        $this->assertFalse(app(OrderService::class)->expire($o));
        $this->assertSame('confirmed', $o->fresh()->status);
        $this->assertSame(2, $v->fresh()->reserved);
    }

    public function test_coupon_expiry_category_minimum_and_customer_limit(): void
    {
        [$v,$cart,$data] = $this->setupCart();
        $coupon = Coupon::create(['code' => 'LIMIT', 'type' => 'fixed', 'value' => 1000, 'minimum' => 1, 'per_customer_limit' => 1, 'expires_at' => now()->addDay()]);
        $data['coupon'] = 'LIMIT';
        app(CheckoutService::class)->place($cart, $data, $data['checkout_key']);
        $another = Cart::create(['token' => (string) Str::uuid()]);
        $another->items()->create(['variant_id' => $v->id, 'quantity' => 1]);
        try {
            app(CheckoutService::class)->quote($another, $data);
            $this->fail('Per-customer limit not enforced.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('coupon', $e->errors());
        }
        $data['email'] = 'different@example.test';
        $coupon->update(['expires_at' => now()->subMinute()]);
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->quote($another, $data);
    }

    public function test_coupon_minimum_and_category_eligibility_are_checked_without_mutation(): void
    {
        [$variant,$cart,$data] = $this->setupCart();
        $coupon = Coupon::create(['code' => 'ELIGIBLE', 'value' => 1000, 'minimum' => 24691, 'expires_at' => now()->addHour()]);
        $data['coupon'] = 'ELIGIBLE';
        try {
            app(CheckoutService::class)->quote($cart, $data);
            $this->fail('Coupon minimum was ignored.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('coupon', $e->errors());
        }
        $coupon->update(['minimum' => 0, 'category_id' => Category::factory()->create()->id]);
        try {
            app(CheckoutService::class)->quote($cart, $data);
            $this->fail('Coupon category was ignored.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('coupon', $e->errors());
        }
        $coupon->update(['category_id' => $variant->product->category_id]);
        $quote = app(CheckoutService::class)->quote($cart, $data);
        $this->assertSame(1000, $quote['discount']);
        $this->assertSame(0, (int) $coupon->fresh()->uses);
        $this->assertSame(0, $variant->fresh()->reserved);
    }
}
