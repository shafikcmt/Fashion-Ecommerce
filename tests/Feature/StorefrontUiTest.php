<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_and_desktop_navigation_have_shop_all_when_cms_is_empty(): void
    {
        $this->withoutVite();
        $this->assertSame(0, ContentBlock::count());
        $response = $this->get('/');
        $response->assertOk();
        $html = $response->getContent();
        preg_match('/<details class="mobile-nav">(.*?)<\/details>/s', $html, $mobile);
        preg_match('/<div class="desktop-nav">(.*?)<\/div>/s', $html, $desktop);
        $this->assertStringContainsString(route('catalog'), $mobile[1]);
        $this->assertStringContainsString('Shop all', $mobile[1]);
        $this->assertStringContainsString(route('catalog'), $desktop[1]);
        $this->withSession(['locale' => 'bn'])->get('/')->assertOk()->assertSee('সব পোশাক');
    }
}
