<?php

namespace Tests\Feature;

use App\Support\Money;
use App\Support\Swatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_uses_the_mamoni_bosraloy_brand_in_both_languages(): void
    {
        $this->withoutVite();
        $this->get('/')->assertOk()->assertSee('Mamoni Bosraloy')->assertSee('মামণি বস্ত্রালয়')->assertDontSee('Aabaran');
        $this->withSession(['locale' => 'bn'])->get('/')->assertOk()->assertSee('মামণি বস্ত্রালয়')->assertSee('MAMONI BOSRALOY');
    }

    public function test_favicon_and_logo_mark_are_branded_vector_files(): void
    {
        $mark = file_get_contents(public_path('images/brand-mark.svg'));
        $this->assertStringContainsString('Mamoni Bosraloy', $mark);
        $this->assertStringNotContainsString('>A<', $mark);
    }

    public function test_bangla_locale_shows_bangla_digits_in_prices(): void
    {
        $this->assertSame('৳1,290', Money::format(129000));
        app()->setLocale('bn');
        $this->assertSame('৳১,২৯০', Money::format(129000));
        $this->assertSame('৳১২৩.৪৫', Money::format(12345));
    }

    public function test_discount_percentage_and_colour_swatches(): void
    {
        $this->assertSame(10, Money::percentOff(100000, 90000));
        $this->assertSame(0, Money::percentOff(100000, 100000));
        $this->assertSame('#7a1f2b', Swatch::hex('Maroon'));
        $this->assertSame('#7a1f2b', Swatch::hex('মেরুন'));
        $this->assertNull(Swatch::hex('Unknown shade'));
    }

    public function test_default_storefront_locale_is_configurable_and_bangla_by_default(): void
    {
        $this->assertSame('en', config('commerce.default_locale'), 'phpunit.xml pins English for existing tests.');
        $this->assertStringContainsString("'bn'", file_get_contents(config_path('commerce.php')));
    }
}
