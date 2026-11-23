<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use App\Support\SearchTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function product(string $name, ?string $bn = null, array $extra = [], array $variant = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'clothes'], ['name' => 'Clothes']);
        $product = Product::factory()->create(array_merge(['name' => $name, 'name_bn' => $bn, 'category_id' => $category->id, 'description' => 'Plain text.'], $extra));
        Variant::factory()->create(array_merge(['product_id' => $product->id], $variant));

        return $product;
    }

    public function test_spelling_variants_expand_across_english_and_bangla(): void
    {
        $this->assertContains('শাড়ি', SearchTerms::expand('shari'));
        $this->assertContains('sari', SearchTerms::expand('শাড়ী'));
        $this->assertContains('পাঞ্জাবি', SearchTerms::expand('punjabi'));
        $this->assertSame([], SearchTerms::expand('   '));
        $this->assertSame(['xyz'], SearchTerms::expand(' XYZ '));
    }

    public function test_search_finds_products_by_bangla_name_and_alias(): void
    {
        $this->product('Dhakai Jamdani Sari', 'ঢাকাই জামদানি শাড়ি');
        $this->product('Cotton Lungi', 'সুতির লুঙ্গি');
        $this->get('/shop?q=শাড়ি')->assertOk()->assertSee('Dhakai Jamdani Sari')->assertDontSee('Cotton Lungi');
        $this->get('/shop?q=shari')->assertOk()->assertSee('Dhakai Jamdani Sari')->assertDontSee('Cotton Lungi');
        $this->get('/shop?q=lungi')->assertOk()->assertSee('Cotton Lungi')->assertDontSee('Dhakai Jamdani Sari');
    }

    public function test_search_covers_fabric_and_category(): void
    {
        $this->product('Plain piece one', null, ['fabric' => 'Rajshahi silk']);
        $kids = Category::create(['name' => 'Kids', 'name_bn' => 'বাচ্চাদের', 'slug' => 'kids']);
        $this->product('Plain piece two', null, ['category_id' => $kids->id]);
        $this->get('/shop?q=silk')->assertOk()->assertSee('Plain piece one')->assertDontSee('Plain piece two');
        $this->get('/shop?q=বাচ্চাদের')->assertOk()->assertSee('Plain piece two')->assertDontSee('Plain piece one');
    }

    public function test_filters_by_fabric_colour_and_stock(): void
    {
        $this->product('Silk one', null, ['fabric' => 'Silk'], ['color' => 'Red']);
        $this->product('Cotton one', null, ['fabric' => 'Cotton'], ['color' => 'Blue']);
        $this->product('Sold out cotton', null, ['fabric' => 'Cotton'], ['color' => 'Blue', 'stock' => 3, 'reserved' => 3]);
        $this->get('/shop?fabric=Silk')->assertOk()->assertSee('Silk one')->assertDontSee('Cotton one');
        $this->get('/shop?color=Blue')->assertOk()->assertSee('Cotton one')->assertDontSee('Silk one');
        $this->get('/shop?color=Blue&stock=1')->assertOk()->assertSee('Cotton one')->assertDontSee('Sold out cotton');
    }

    public function test_filter_dropdowns_list_real_colours_and_fabrics(): void
    {
        $this->product('Silk one', null, ['fabric' => 'Silk'], ['color' => 'Maroon']);
        $this->get('/shop')->assertOk()->assertSee('Maroon</option>', false)->assertSee('Silk</option>', false);
    }
}
