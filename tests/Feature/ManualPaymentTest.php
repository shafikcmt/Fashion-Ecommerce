<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\Variant;
use App\Services\OrderService;
use App\Support\Analytics;
use App\Support\ManualPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
    }

    private function checkoutData(array $overrides = []): array
    {
        $variant = Variant::factory()->create();
        $cart = Cart::create(['token' => (string) Str::uuid()]);
        $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
        $zone = ShippingZone::create(['name' => 'All', 'districts' => ['*'], 'charge' => 7000]);
        $key = (string) Str::uuid();
        $this->withSession(['cart_token' => $cart->token, 'checkout_key' => $key]);

        return array_merge(['name' => 'Buyer', 'phone' => '01712345678', 'address' => 'Road 1', 'district' => 'Dhaka', 'shipping_zone_id' => $zone->id, 'payment_method' => 'cod', 'checkout_key' => $key, 'terms' => '1'], $overrides);
    }

    private function place(array $data): void
    {
        $this->post(route('checkout.review'), $data)->assertOk();
        $this->post(route('checkout.place'), ['checkout_key' => $data['checkout_key']])->assertRedirect();
    }

    public function test_wallet_methods_are_offered_only_when_a_valid_number_is_configured(): void
    {
        $this->assertSame([], ManualPayments::available());
        Setting::create(['key' => 'bkash_number', 'value' => '01711111111']);
        Setting::create(['key' => 'nagad_number', 'value' => 'not a number']);
        $this->assertSame(['bkash_manual'], array_keys(ManualPayments::available()));
        $this->checkoutData();
        $this->get(route('checkout'))->assertOk()->assertSee('value="bkash_manual"', false)->assertSee('01711111111')->assertDontSee('value="nagad_manual"', false);
    }

    public function test_cod_still_works_and_wallet_is_rejected_when_not_configured(): void
    {
        $data = $this->checkoutData();
        $this->place($data);
        $this->assertSame('cod', Order::firstOrFail()->payment_method);
        $other = $this->checkoutData(['payment_method' => 'bkash_manual', 'payment_sender' => '01712345678', 'payment_reference' => 'ABC123XYZ9']);
        $this->post(route('checkout.review'), $other)->assertSessionHasErrors('payment_method');
    }

    public function test_wallet_order_is_unpaid_with_reference_until_staff_verify_it(): void
    {
        Setting::create(['key' => 'bkash_number', 'value' => '01711111111']);
        $data = $this->checkoutData(['payment_method' => 'bkash_manual', 'payment_sender' => '01812345678', 'payment_reference' => 'abc123xyz9']);
        $this->place($data);
        $order = Order::firstOrFail();
        $this->assertSame('bkash_manual', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('ABC123XYZ9', $order->payment_reference);
        $this->assertSame('01812345678', $order->payment_sender);
        $this->assertTrue($order->reservation_expires_at->isAfter(now()->addDays(2)));
        app(OrderService::class)->verifyManualPayment($order);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->expectException(ValidationException::class);
        app(OrderService::class)->verifyManualPayment($order);
    }

    public function test_wallet_requires_sender_and_a_plausible_transaction_id(): void
    {
        Setting::create(['key' => 'bkash_number', 'value' => '01711111111']);
        $data = $this->checkoutData(['payment_method' => 'bkash_manual']);
        $this->post(route('checkout.review'), $data)->assertSessionHasErrors(['payment_sender', 'payment_reference']);
        $this->post(route('checkout.review'), $data + ['payment_sender' => '123', 'payment_reference' => 'x'])->assertSessionHasErrors(['payment_sender', 'payment_reference']);
    }

    public function test_a_transaction_id_cannot_be_used_twice(): void
    {
        Setting::create(['key' => 'bkash_number', 'value' => '01711111111']);
        $this->place($this->checkoutData(['payment_method' => 'bkash_manual', 'payment_sender' => '01812345678', 'payment_reference' => 'DUP123456']));
        $second = $this->checkoutData(['payment_method' => 'bkash_manual', 'payment_sender' => '01912345678', 'payment_reference' => 'dup123456']);
        $this->post(route('checkout.review'), $second)->assertSessionHasErrors('payment_reference');
        $this->assertSame(1, Order::count());
    }

    public function test_customer_cannot_self_cancel_a_wallet_order(): void
    {
        Setting::create(['key' => 'nagad_number', 'value' => '01711111111']);
        $this->place($this->checkoutData(['payment_method' => 'nagad_manual', 'payment_sender' => '01812345678', 'payment_reference' => 'NGD1234567']));
        $order = Order::firstOrFail();
        $this->post(route('orders.cancel', $order))->assertStatus(422);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertFalse(ManualPayments::isManual('cod'));
    }

    public function test_pixel_and_analytics_ids_are_validated_and_only_rendered_when_valid(): void
    {
        $this->get('/')->assertOk()->assertDontSee('fbevents.js')->assertDontSee('googletagmanager');
        Setting::create(['key' => 'facebook_pixel_id', 'value' => "123456789012345'); alert(1);//"]);
        Setting::create(['key' => 'ga_measurement_id', 'value' => 'G-ABC123XYZ']);
        $this->assertNull(Analytics::facebookPixelId());
        $this->get('/')->assertOk()->assertDontSee('fbevents.js')->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false);
        Setting::where('key', 'facebook_pixel_id')->update(['value' => '123456789012345']);
        $this->get('/')->assertOk()->assertSee("fbq('init','123456789012345')", false);
    }
}
