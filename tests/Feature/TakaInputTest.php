<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\ManageCouponRecords;
use App\Filament\Resources\Pages\ManageShippingZoneRecords;
use App\Filament\Resources\Pages\ManageVariantRecords;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use App\Models\Variant;
use App\Support\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TakaInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        Filament::setCurrentPanel('admin');
    }

    public function test_taka_and_paisa_conversion_rounds_to_whole_taka(): void
    {
        $this->assertSame(145000, Money::fromTaka('1450'));
        $this->assertSame(145100, Money::fromTaka('1450.6'));
        $this->assertSame(145000, Money::fromTaka(1450.4));
        $this->assertNull(Money::fromTaka(''));
        $this->assertSame(1450, Money::toTaka(145000));
        $this->assertSame(14.5, Money::toTaka(1450));
        $this->assertNull(Money::toTaka(null));
    }

    public function test_variant_prices_are_typed_in_taka_and_stored_in_paisa(): void
    {
        $product = Product::factory()->create();
        Livewire::test(ManageVariantRecords::class)->callAction('create', data: ['product_id' => $product->id, 'sku' => 'T-1', 'size' => 'Free size', 'color' => 'Red', 'price' => '2190', 'sale_price' => '1990', 'low_stock_threshold' => 5])->assertHasNoActionErrors();
        $variant = Variant::where('sku', 'T-1')->firstOrFail();
        $this->assertSame(219000, $variant->price);
        $this->assertSame(199000, $variant->sale_price);
    }

    public function test_sale_price_above_the_regular_price_is_rejected(): void
    {
        $product = Product::factory()->create();
        Livewire::test(ManageVariantRecords::class)->callAction('create', data: ['product_id' => $product->id, 'sku' => 'T-2', 'size' => 'M', 'color' => 'Blue', 'price' => '500', 'sale_price' => '900', 'low_stock_threshold' => 5])->assertHasActionErrors(['sale_price']);
        $this->assertSame(0, Variant::where('sku', 'T-2')->count());
    }

    public function test_existing_variant_prices_show_in_taka_when_editing(): void
    {
        $variant = Variant::factory()->create(['price' => 145000, 'sale_price' => null]);
        Livewire::test(ManageVariantRecords::class)->mountTableAction('edit', $variant)->assertTableActionDataSet(['price' => 1450]);
    }

    public function test_coupon_values_use_taka_for_fixed_and_a_plain_number_for_percent(): void
    {
        Livewire::test(ManageCouponRecords::class)->callAction('create', data: ['code' => 'EID100', 'type' => 'fixed', 'value' => '100', 'minimum' => '1000', 'usage_limit' => 50, 'per_customer_limit' => 1, 'expires_at' => now()->addMonth(), 'active' => true])->assertHasNoActionErrors();
        Livewire::test(ManageCouponRecords::class)->callAction('create', data: ['code' => 'TEN', 'type' => 'percent', 'value' => '10', 'minimum' => '0', 'usage_limit' => 50, 'per_customer_limit' => 1, 'expires_at' => now()->addMonth(), 'active' => true])->assertHasNoActionErrors();
        $fixed = Coupon::where('code', 'EID100')->firstOrFail();
        $this->assertSame(10000, $fixed->value);
        $this->assertSame(100000, $fixed->minimum);
        $this->assertSame(10, Coupon::where('code', 'TEN')->firstOrFail()->value);
    }

    public function test_shipping_charge_is_typed_in_taka(): void
    {
        Livewire::test(ManageShippingZoneRecords::class)->callAction('create', data: ['name' => 'Dhaka', 'districts' => ['Dhaka'], 'charge' => '70', 'active' => true])->assertHasNoActionErrors();
        $this->assertSame(7000, ShippingZone::where('name', 'Dhaka')->firstOrFail()->charge);
    }
}
