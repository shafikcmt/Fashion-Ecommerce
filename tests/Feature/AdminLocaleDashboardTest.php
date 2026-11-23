<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocaleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        return $admin;
    }

    public function test_admin_stays_english_while_the_storefront_is_bangla(): void
    {
        $this->admin();
        $this->withSession(['locale' => 'bn'])->get('/admin')->assertOk()->assertSee('Sales in the last 7 days')->assertSee('lang="en"', false);
        $this->withSession(['locale' => 'bn'])->get('/')->assertOk()->assertSee('lang="bn"', false);
    }

    public function test_admin_can_switch_the_admin_language_without_changing_the_storefront(): void
    {
        $this->admin();
        $this->post(route('admin.locale'), ['admin_locale' => 'bn'])->assertRedirect();
        $this->get('/admin')->assertOk()->assertSee('গত ৭ দিনের বিক্রি')->assertSee('lang="bn"', false);
        $this->get('/')->assertOk()->assertSee('lang="en"', false);
        $this->post(route('admin.locale'), ['admin_locale' => 'fr'])->assertSessionHasErrors('admin_locale');
    }

    public function test_customers_and_guests_cannot_switch_the_admin_language(): void
    {
        $this->post(route('admin.locale'), ['admin_locale' => 'bn'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'customer']))->post(route('admin.locale'), ['admin_locale' => 'bn'])->assertForbidden();
    }

    public function test_dashboard_shows_pending_reviews_and_daily_sales(): void
    {
        $this->admin();
        ProductReview::factory()->pending()->create(['product_id' => Product::factory()]);
        $response = $this->get('/admin')->assertOk();
        $response->assertSee('reviews waiting for approval')->assertSee(now()->toDateString());
        $this->assertSame(0, Order::count());
    }
}
