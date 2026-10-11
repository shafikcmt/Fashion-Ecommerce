<?php

namespace Tests\Feature;

use App\Livewire\ProductPurchase;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Media;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\Variant;
use App\Payments\DisabledProvider;
use App\Services\CartService;
use App\Services\CatalogCsvService;
use App\Services\CheckoutService;
use App\Services\MediaService;
use App\Services\OrderService;
use Filament\Panel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
    }

    private function variant(int $stock = 5): Variant
    {
        return Variant::factory()->create(['stock' => $stock]);
    }

    private function cart(Variant $variant, int $qty = 1): Cart
    {
        $cart = Cart::create(['token' => (string) Str::uuid()]);
        $cart->items()->create(['variant_id' => $variant->id, 'quantity' => $qty]);

        return $cart;
    }

    private function data(array $overrides = []): array
    {
        $zone = ShippingZone::firstOrCreate(['name' => 'Dhaka'], ['districts' => ['Dhaka'], 'charge' => 8000]);

        return array_merge(['name' => 'Buyer', 'email' => 'buyer@example.test', 'phone' => '01712345678', 'address' => '12 Test Road', 'district' => 'Dhaka', 'shipping_zone_id' => $zone->id, 'payment_method' => 'cod'], $overrides);
    }

    private function order(Variant $variant, int $qty = 1, array $data = []): Order
    {
        return app(CheckoutService::class)->place($this->cart($variant, $qty), $this->data($data), (string) Str::uuid());
    }

    public function test_customers_cannot_access_admin_and_roles_have_scoped_permissions(): void
    {
        $p = Product::factory()->create();
        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/admin/products')->assertForbidden();
        $catalog = User::factory()->create(['role' => 'catalog_manager']);
        $this->actingAs($catalog);
        $this->assertTrue(Gate::allows('update', $p));
        $this->assertTrue(Gate::allows('create', Media::class));
        $this->assertFalse(Gate::allows('create', Page::class));
        $this->assertFalse(Gate::allows('create', User::class));
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/products')->assertOk();
        $editor = User::factory()->create(['role' => 'content_editor']);
        $this->actingAs($editor);
        $this->assertTrue(Gate::allows('create', Page::class));
        $this->assertFalse(Gate::allows('create', Product::class));
        $unverified = User::factory()->unverified()->create(['role' => 'super_admin']);
        $this->assertFalse($unverified->canAccessPanel(Panel::make()));
    }

    public function test_super_admin_can_render_every_cms_resource_and_reports(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        foreach (['products', 'variants', 'categories', 'collections', 'brands', 'tags', 'media', 'pages', 'content-blocks', 'settings', 'shipping-zones', 'coupons', 'orders', 'return-requests', 'users', 'audit-logs', 'stock-movements', 'reports'] as $slug) {
            $this->get('/admin/'.$slug)->assertOk();
        }
    }

    public function test_duplicate_sku_is_rejected_by_database(): void
    {
        $v = $this->variant();
        $this->expectException(QueryException::class);
        Variant::factory()->create(['sku' => $v->sku]);
    }

    public function test_duplicate_size_color_combination_is_rejected(): void
    {
        $v = $this->variant();
        $this->expectException(QueryException::class);
        Variant::factory()->create(['product_id' => $v->product_id, 'size' => $v->size, 'color' => $v->color]);
    }

    public function test_sale_price_above_price_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        Variant::factory()->create(['price' => 10000, 'sale_price' => 10001]);
    }

    public function test_checkout_uses_database_prices_and_creates_immutable_snapshots(): void
    {
        $v = $this->variant();
        $o = $this->order($v, 2, ['subtotal' => 1, 'discount' => 9999999, 'total' => 1]);
        $this->assertSame(250100, (int) $o->subtotal);
        $this->assertSame(258100, (int) $o->total);
        $this->assertSame(2, $v->fresh()->reserved);
        $v->update(['price' => 999000]);
        $this->assertSame(125050, (int) $o->items->first()->unit_price);
        $this->expectException(\LogicException::class);
        $o->items->first()->update(['product_name' => 'Changed']);
    }

    public function test_competing_checkout_cannot_oversell_and_rolls_back(): void
    {
        $v = $this->variant(1);
        $this->order($v);
        try {
            $this->order($v);
            $this->fail('Expected unavailable stock.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cart', $e->errors());
        }
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $v->fresh()->reserved);
    }

    public function test_checkout_retry_returns_same_order_and_reserves_once(): void
    {
        $v = $this->variant();
        $cart = $this->cart($v);
        $key = (string) Str::uuid();
        $data = $this->data();
        $first = app(CheckoutService::class)->place($cart, $data, $key);
        $retry = app(CheckoutService::class)->place($cart, $data, $key);
        $this->assertSame($first->id, $retry->id);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $v->fresh()->reserved);
    }

    public function test_coupon_limits_and_integer_discount_are_enforced(): void
    {
        $v = $this->variant();
        $coupon = Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'minimum' => 10000, 'usage_limit' => 1, 'per_customer_limit' => 1, 'expires_at' => now()->addDay()]);
        $o = $this->order($v, 1, ['coupon' => 'ten']);
        $this->assertSame(12505, (int) $o->discount);
        $this->assertSame(120545, (int) $o->total);
        try {
            $this->order($v, 1, ['coupon' => 'TEN']);
            $this->fail('Expected coupon limit.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('coupon', $e->errors());
        }
        $this->assertSame(1, (int) $coupon->fresh()->uses);
        $this->assertSame(1, Order::count());
    }

    public function test_cancel_and_expiry_release_stock_and_coupon_once(): void
    {
        $v = $this->variant();
        $coupon = Coupon::create(['code' => 'SAVE', 'value' => 1000, 'expires_at' => now()->addDay()]);
        $o = $this->order($v, 1, ['coupon' => 'SAVE']);
        app(OrderService::class)->transition($o, 'cancelled');
        $this->assertSame(0, $v->fresh()->reserved);
        $this->assertSame(0, (int) $coupon->fresh()->uses);
        $o2 = $this->order($v);
        $o2->update(['reservation_expires_at' => now()->subMinute()]);
        $this->artisan('commerce:expire-reservations')->assertSuccessful();
        $this->assertSame('cancelled', $o2->fresh()->status);
        $this->assertSame(0, $v->fresh()->reserved);
    }

    public function test_shipping_zone_and_disabled_payment_are_rejected(): void
    {
        $v = $this->variant();
        try {
            $this->order($v, 1, ['district' => 'Chattogram']);
            $this->fail('Invalid zone accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shipping_zone_id', $e->errors());
        }
        $this->expectException(ValidationException::class);
        $this->order($v, 1, ['payment_method' => 'bkash']);
    }

    public function test_state_transitions_consumption_and_explicit_return_approval(): void
    {
        $v = $this->variant();
        $o = $this->order($v, 2);
        $svc = app(OrderService::class);
        $svc->transition($o, 'confirmed');
        $svc->transition($o, 'shipped');
        $this->assertSame(3, $v->fresh()->stock);
        $this->assertSame(0, $v->fresh()->reserved);
        try {
            $svc->transition($o, 'cancelled');
            $this->fail('Cannot cancel shipped order.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $svc->transition($o, 'delivered');
        $svc->collectCod($o);
        $r = $svc->requestReturn($o, 'The garment does not fit.');
        try {
            $svc->restockReturn($r);
            $this->fail('Unapproved return was restocked.');
        } catch (ValidationException $e) {
            $this->assertSame(3, $v->fresh()->stock);
        }
        $svc->reviewReturn($r, 'approved', 'Return received in acceptable condition.');
        $svc->restockReturn($r);
        $this->assertSame(5, $v->fresh()->stock);
        $svc->recordRefund($o);
        $this->assertSame('refunded', $o->fresh()->payment_status);
        $this->expectException(ValidationException::class);
        $svc->restockReturn($r);
    }

    public function test_order_receipt_rejects_another_customer_and_accepts_signed_link(): void
    {
        $o = $this->order($this->variant());
        $this->actingAs(User::factory()->create())->get(route('orders.show', $o))->assertForbidden();
        $this->get(URL::temporarySignedRoute('orders.show', now()->addMinute(), ['order' => $o->public_id]))->assertOk();
    }

    public function test_upload_validation_and_referenced_media_cannot_be_deleted(): void
    {
        $disk = 'media-test-'.Str::uuid();
        Storage::fake($disk);
        config(['commerce.media_disk' => $disk]);
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('dress.jpg'), 'Blue dress');
        $p = Product::factory()->create();
        $p->media()->attach($media->id);
        try {
            app(MediaService::class)->delete($media);
            $this->fail('Referenced image deleted.');
        } catch (ValidationException $e) {
            $this->assertDatabaseHas('media', ['id' => $media->id]);
        }
        $this->expectException(ValidationException::class);
        app(MediaService::class)->upload(UploadedFile::fake()->create('fake.jpg', 1, 'text/html'), 'Bad file');
    }

    public function test_guest_cart_is_merged_without_exceeding_available_stock(): void
    {
        $v = $this->variant(3);
        $guest = $this->cart($v, 2);
        $u = User::factory()->create();
        $owned = Cart::create(['token' => (string) Str::uuid(), 'user_id' => $u->id]);
        $owned->items()->create(['variant_id' => $v->id, 'quantity' => 2]);
        session(['cart_token' => $guest->token]);
        app(CartService::class)->merge($u->id);
        $this->assertSame(3, (int) $owned->items()->first()->quantity);
        $this->assertDatabaseMissing('carts', ['id' => $guest->id]);
    }

    public function test_storefront_renders_and_content_is_escaped(): void
    {
        $v = $this->variant();
        $v->product->update(['featured' => true, 'description' => '<script>alert(1)</script>']);
        foreach (['/', '/shop', '/shop?sort=price_asc', '/products/'.$v->product->slug, '/bag', '/checkout', '/login', '/register', '/forgot-password', '/sitemap.xml'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/products/'.$v->product->slug)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post('/locale', ['locale' => 'bn'])->assertRedirect();
        $this->get('/')->assertSee('lang="bn"', false);
    }

    public function test_livewire_purchase_uses_server_variant_and_quantity(): void
    {
        $v = $this->variant(2);
        Livewire::test(ProductPurchase::class, ['productId' => $v->product_id])->set('size', 'M')->set('color', 'Indigo')->set('quantity', 2)->call('add')->assertHasNoErrors();
        $this->assertSame(2, (int) Cart::first()->items()->first()->quantity);
    }

    public function test_csv_invalid_row_reports_error_and_changes_nothing(): void
    {
        $v = $this->variant();
        $file = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($file, implode(',', CatalogCsvService::HEADERS)."\n".$v->product->slug.','.$v->sku.",M,Indigo,9999,,5\nmissing,NEW,M,Blue,10000,,2\n");
        try {
            app(CatalogCsvService::class)->import($file);
            $this->fail('Invalid row accepted.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Row 3', implode(' ', $e->errors()['csv']));
            $this->assertSame(125050, $v->fresh()->price);
        } finally {
            unlink($file);
        }
    }

    public function test_online_payment_providers_are_disabled(): void
    {
        foreach (['bkash', 'nagad', 'sslcommerz'] as $name) {
            $this->assertFalse((new DisabledProvider($name))->enabled());
        }
        $this->expectException(\LogicException::class);
        (new DisabledProvider('bkash'))->verify('client-success');
    }

    public function test_valid_csv_changes_price_and_records_stock_adjustment_then_exports(): void
    {
        $variant = $this->variant();
        $file = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($file, implode(',', CatalogCsvService::HEADERS)."\n".$variant->product->slug.','.$variant->sku.",M,Indigo,200001,,7\n");
        try {
            $this->assertSame(1, app(CatalogCsvService::class)->import($file));
        } finally {
            unlink($file);
        }
        $this->assertSame(200001, $variant->fresh()->price);
        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $variant->id, 'quantity' => 2, 'reason' => 'CSV import']);
        $stream = fopen('php://memory', 'w+');
        app(CatalogCsvService::class)->export($stream);
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $this->assertStringContainsString($variant->sku.',M,Indigo,200001,,7', $csv);
    }
}
