<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\ManageSettingRecords;
use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        Filament::setCurrentPanel('admin');
    }

    public function test_every_setting_key_the_storefront_reads_can_be_chosen_in_admin(): void
    {
        foreach (['brand_name', 'brand_name_bn', 'brand_story_bn', 'whatsapp', 'facebook_url', 'free_delivery_min', 'bkash_number', 'nagad_number', 'facebook_pixel_id', 'ga_measurement_id', 'logo_media_id'] as $key) {
            $this->assertArrayHasKey($key, SettingResource::KEYS, $key.' is missing from the admin list');
        }
    }

    public function test_bangla_brand_name_can_be_saved_and_appears_on_the_storefront(): void
    {
        Livewire::test(ManageSettingRecords::class)->callAction('create', data: ['key' => 'brand_name_bn', 'value' => 'মা মনি বস্ত্রালয়'])->assertHasNoActionErrors();
        $this->assertSame('মা মনি বস্ত্রালয়', Setting::value('brand_name_bn'));
        $this->withSession(['locale' => 'bn'])->get('/')->assertOk()->assertSee('মা মনি বস্ত্রালয়');
    }
}
