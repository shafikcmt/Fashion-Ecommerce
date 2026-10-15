<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\ManageCategoryRecords;
use App\Filament\Resources\Pages\ManageOrderRecords;
use App\Filament\Resources\Pages\ManageProductRecords;
use App\Filament\Resources\Pages\ManageVariantRecords;
use App\Models\Cart;
use App\Models\Category;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\Variant;
use App\Services\CheckoutService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CmsActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        Filament::setCurrentPanel('admin');
    }

    public function test_catalog_creation_and_audit_through_real_admin_action(): void
    {
        Livewire::test(ManageCategoryRecords::class)->callAction('create', data: ['name' => 'Test category', 'slug' => 'test-category'])->assertHasNoActionErrors();
        $this->assertDatabaseHas('categories', ['slug' => 'test-category']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'created', 'subject_type' => Category::class]);
    }

    public function test_stock_adjustment_admin_action_records_history_and_preserves_reservations(): void
    {
        $v = Variant::factory()->create(['stock' => 5, 'reserved' => 2]);
        Livewire::test(ManageVariantRecords::class)->callTableAction('adjustStock', $v, data: ['quantity' => 3, 'reason' => 'Received shipment'])->assertHasNoTableActionErrors()->assertNotified('Stock adjusted');
        $this->assertSame(8, $v->fresh()->stock);
        $this->assertSame(2, $v->fresh()->reserved);
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'quantity' => 3, 'reason' => 'Received shipment']);
    }

    public function test_dashboard_and_all_product_form_controls_render(): void
    {
        $this->get('/admin')->assertOk();
        Livewire::test(ManageProductRecords::class)->mountAction('create')->assertActionMounted('create');
    }

    public function test_admin_order_actions_confirm_ship_deliver_and_collect_cod(): void
    {
        $variant = Variant::factory()->create(['stock' => 2]);
        $cart = Cart::create(['token' => (string) Str::uuid()]);
        $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
        $zone = ShippingZone::create(['name' => 'Dhaka', 'districts' => ['Dhaka'], 'charge' => 0]);
        $order = app(CheckoutService::class)->place($cart, ['name' => 'Buyer', 'email' => 'buyer@example.test', 'phone' => '01712345678', 'address' => 'Road', 'district' => 'Dhaka', 'shipping_zone_id' => $zone->id], (string) Str::uuid());
        foreach (['confirmed', 'shipped', 'delivered', 'collectCod'] as $action) {
            Livewire::test(ManageOrderRecords::class)->callTableAction($action, $order->fresh())->assertHasNoTableActionErrors()->assertNotified($action === 'collectCod' ? 'COD collection recorded' : 'Order '.$action);
        }
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame(0, $variant->fresh()->reserved);
    }
}
