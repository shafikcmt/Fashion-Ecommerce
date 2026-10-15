<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\Variant;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo seeding is disabled in production.');
        }
        DB::transaction(function () {
            foreach ([
                'brand_name' => 'Aabaran', 'brand_color' => '#333D79', 'brand_story' => 'A considered wardrobe. A little more you.',
                'contact_email' => 'hello@example.test', 'contact_phone' => '+880 1700 000000', 'contact_address' => 'Dhaka, Bangladesh',
                'announcement' => 'Made for the way you move. Delivered across Bangladesh.',
                'announcement_bn' => 'আপনার প্রতিদিনের জন্য। সারা বাংলাদেশে ডেলিভারি।',
                'seo_description' => 'Thoughtfully chosen everyday fashion in Bangladesh. Shop in BDT with cash on delivery.',
            ] as $key => $value) {
                Setting::firstOrCreate(['key' => $key], ['value' => $value]);
            }
            $categories = [];
            foreach (['women' => ['Women', 'নারী'], 'men' => ['Men', 'পুরুষ'], 'essentials' => ['Essentials', 'প্রয়োজনীয় পোশাক']] as $slug => [$name,$bn]) {
                $categories[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'name_bn' => $bn]);
            }
            $collection = Collection::firstOrCreate(['slug' => 'everyday-edit'], ['name' => 'The everyday edit', 'name_bn' => 'প্রতিদিনের সংগ্রহ']);
            $brand = Brand::firstOrCreate(['slug' => 'aabaran'], ['name' => 'Aabaran', 'name_bn' => 'আবরণ']);
            $photos = [];
            foreach (['hero', 'linen', 'shirt', 'knit', 'dress', 'denim', 'jacket'] as $name) {
                $source = public_path('demo/'.$name.'.jpg');
                if (is_file($source)) {
                    $path = 'demo/'.$name.'.jpg';
                    Storage::disk('public')->put($path, file_get_contents($source));
                    $photos[$name] = Media::firstOrCreate(['path' => $path], ['disk' => 'public', 'alt' => 'Demo fashion photograph: '.$name, 'mime' => 'image/jpeg', 'bytes' => filesize($source)]);
                }
            }
            $products = [
                ['linen-relaxed-shirt', 'The relaxed linen shirt', 'লিনেন শার্ট', 'women', 249000, 'linen', 'Sky blue'],
                ['cotton-overshirt', 'Everyday cotton overshirt', 'কটন ওভারশার্ট', 'men', 289000, 'shirt', 'White'],
                ['soft-knit', 'The soft knit', 'নরম নিট পোশাক', 'essentials', 319000, 'knit', 'Oat'],
                ['weekend-dress', 'The weekend dress', 'উইকএন্ড ড্রেস', 'women', 429000, 'dress', 'Sage'],
                ['straight-denim', 'Straight-leg denim', 'স্ট্রেইট ডেনিম', 'essentials', 349000, 'denim', 'Indigo'],
                ['everyday-jacket', 'The everyday layer', 'প্রতিদিনের জ্যাকেট', 'men', 499000, 'jacket', 'Sand'],
            ];
            foreach ($products as $index => [$slug,$name,$bn,$category,$price,$photo,$color]) {
                $product = Product::firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'name_bn' => $bn, 'category_id' => $categories[$category]->id, 'brand_id' => $brand->id, 'status' => 'published', 'featured' => true,
                    'description' => 'An easy silhouette, soft texture and room to move. Designed for slow mornings, busy days and everything in between. Development sample; confirm actual specifications before selling.',
                    'description_bn' => 'আরামদায়ক কাটিং ও নরম কাপড়। আপনার প্রতিদিনের জন্য। এটি ডেভেলপমেন্ট নমুনা; বিক্রির আগে প্রকৃত বিবরণ নিশ্চিত করুন।',
                    'care' => "Gentle cold wash with similar colours.\nDry in shade. Warm iron if needed.",
                    'size_guide' => "S: chest 36–38 in\nM: chest 38–40 in\nL: chest 40–42 in\nXL: chest 42–44 in\nDevelopment sizing only; replace with actual garment measurements.",
                ]);
                $product->collections()->syncWithoutDetaching([$collection->id]);
                if (isset($photos[$photo])) {
                    $product->media()->syncWithoutDetaching([$photos[$photo]->id => ['position' => 0]]);
                }
                foreach (['S', 'M', 'L', 'XL'] as $size) {
                    $v = Variant::firstOrCreate(['sku' => 'AAB-'.($index + 1).'-'.$size], ['product_id' => $product->id, 'size' => $size, 'color' => $color, 'price' => $price, 'sale_price' => $index === 0 ? 219000 : null]);
                    if ($v->wasRecentlyCreated) {
                        app(InventoryService::class)->adjust($v->id, 12, 'Development opening stock');
                    }
                }
            }
            ContentBlock::firstOrCreate(['type' => 'hero', 'position' => 0], [
                'title' => 'Everyday, beautifully dressed.', 'title_bn' => 'প্রতিদিন, নতুন সাজে।',
                'body' => 'Easy silhouettes. Thoughtful details. Pieces that feel like you, wherever the day takes you.',
                'body_bn' => 'আরামদায়ক পোশাক ও যত্নের ছোঁয়া। প্রতিটি দিনের জন্য আপনার পছন্দের সংগ্রহ।',
                'url' => '/collection/everyday-edit', 'link_label' => 'Explore the everyday edit', 'media_id' => $photos['hero']->id ?? null,
            ]);
            ContentBlock::firstOrCreate(['type' => 'promotion', 'position' => 0], [
                'title' => 'Less effort. More you.', 'title_bn' => 'সহজ সাজে, নিজের মতো।',
                'body' => 'Build your wardrobe around pieces that work together. Discover our everyday essentials.',
                'body_bn' => 'আপনার প্রতিদিনের জন্য মানানসই পোশাক। দেখুন আমাদের নতুন সংগ্রহ।',
                'url' => '/category/essentials', 'link_label' => 'Shop essentials', 'media_id' => $photos['linen']->id ?? null,
            ]);
            foreach (['Shop all' => ['/shop', 'সব পোশাক'], 'Women' => ['/category/women', 'নারী'], 'Men' => ['/category/men', 'পুরুষ'], 'Essentials' => ['/category/essentials', 'প্রয়োজনীয় পোশাক'], 'The everyday edit' => ['/collection/everyday-edit', 'প্রতিদিনের সংগ্রহ']] as $title => [$url,$bn]) {
                ContentBlock::firstOrCreate(['type' => 'navigation', 'url' => $url], ['title' => $title, 'title_bn' => $bn, 'position' => ContentBlock::where('type', 'navigation')->count()]);
            }
            $pages = [
                'about' => ['Our story', 'আমাদের গল্প', "Aabaran is a development store for thoughtfully chosen everyday fashion.\n\nReplace this story with your real brand history before launch."],
                'contact' => ['Contact us', 'যোগাযোগ', "Write to hello@example.test or call +880 1700 000000.\n\nThese are development contact details. Configure your real contact information in Settings."],
                'faq' => ['Frequently asked questions', 'সাধারণ প্রশ্ন', "How do I pay?\nCash on delivery is currently available.\n\nHow do I choose a size?\nCheck the product’s size guide, or contact the store.\n\nCan I track an order?\nYour order page shows its status and any courier tracking number."],
                'delivery' => ['Delivery information', 'ডেলিভারি তথ্য', "We support cash on delivery across Bangladesh.\nShipping charges depend on your selected delivery zone.\n\nDevelopment copy: configure delivery expectations before launch."],
                'returns' => ['Returns & exchanges', 'রিটার্ন ও পরিবর্তন', "Request a return from your delivered order’s page. Our team reviews requests.\nItems are restocked after approval and receipt.\n\nConfigure your eligibility period and commercial policy before launch."],
                'privacy' => ['Privacy policy', 'গোপনীয়তা নীতি', "We store account, address and order information to fulfill purchases.\nContact the store for privacy requests.\n\nDevelopment placeholder: obtain an appropriate policy before launch."],
                'terms' => ['Terms of service', 'শর্তাবলি', "Prices are in BDT. COD orders require store confirmation.\nPending inventory holds expire after 24 hours.\n\nDevelopment placeholder: replace with reviewed terms before launch."],
            ];
            foreach ($pages as $slug => [$title,$bn,$body]) {
                Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'title_bn' => $bn, 'body' => $body, 'body_bn' => 'এটি ডেভেলপমেন্ট নমুনা। প্রকাশের আগে আপনার দোকানের তথ্য ও নীতিমালা যোগ করুন।', 'published' => true]);
                ContentBlock::firstOrCreate(['type' => 'footer', 'url' => '/pages/'.$slug], ['title' => $title, 'title_bn' => $bn, 'position' => ContentBlock::where('type', 'footer')->count()]);
            }
            ShippingZone::firstOrCreate(['name' => 'Dhaka delivery'], ['districts' => ['Dhaka'], 'charge' => 8000]);
            ShippingZone::firstOrCreate(['name' => 'Nationwide delivery'], ['districts' => ['*'], 'charge' => 15000]);
        });
    }
}
