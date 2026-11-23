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
                'brand_name' => 'Mamoni Bosraloy', 'brand_name_bn' => 'মামণি বস্ত্রালয়', 'brand_color' => '#7A1F2B',
                'brand_story' => 'Sari, Panjabi, Three-piece and everyday wear for the whole family. Trusted quality at fair prices.',
                'brand_story_bn' => 'শাড়ি, পাঞ্জাবি, থ্রি-পিস ও পরিবারের সবার প্রতিদিনের পোশাক। বিশ্বস্ত মান, ন্যায্য দাম।',
                'contact_email' => 'hello@example.test', 'contact_phone' => '+880 1700 000000', 'whatsapp' => '8801700000000',
                'contact_address' => 'Dhaka, Bangladesh',
                'announcement' => 'Cash on delivery all over Bangladesh · 7-day easy return',
                'announcement_bn' => 'সারা বাংলাদেশে ক্যাশ অন ডেলিভারি · ৭ দিনের মধ্যে সহজ রিটার্ন',
                'seo_description' => 'Mamoni Bosraloy: sari, panjabi, three-piece, kurti, lungi and kids wear in Bangladesh. Prices in BDT with cash on delivery.',
            ] as $key => $value) {
                Setting::firstOrCreate(['key' => $key], ['value' => $value]);
            }

            $categories = [];
            foreach ([
                'shari' => ['Sari', 'শাড়ি'], 'panjabi' => ['Panjabi', 'পাঞ্জাবি'], 'three-piece' => ['Three-piece', 'থ্রি-পিস'],
                'kurti' => ['Kurti', 'কুর্তি'], 'orna-hijab' => ['Orna & Hijab', 'ওড়না ও হিজাব'], 'lungi' => ['Lungi', 'লুঙ্গি'],
                'shirt-tshirt' => ['Shirt & T-shirt', 'শার্ট ও টি-শার্ট'], 'kids' => ['Kids', 'বাচ্চাদের পোশাক'], 'winter' => ['Winter', 'শীতের পোশাক'],
            ] as $slug => [$name, $bn]) {
                $categories[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'name_bn' => $bn]);
            }
            $collections = [];
            foreach ([
                'eid-collection' => ['Eid Collection', 'ঈদ কালেকশন'], 'pohela-boishakh' => ['Pohela Boishakh', 'পহেলা বৈশাখ'],
                'wedding-collection' => ['Wedding Collection', 'বিয়ের কালেকশন'],
            ] as $slug => [$name, $bn]) {
                $collections[$slug] = Collection::firstOrCreate(['slug' => $slug], ['name' => $name, 'name_bn' => $bn]);
            }
            $brand = Brand::firstOrCreate(['slug' => 'mamoni-bosraloy'], ['name' => 'Mamoni Bosraloy', 'name_bn' => 'মামণি বস্ত্রালয়']);

            $photos = [];
            foreach (glob(public_path('demo/*.svg')) ?: [] as $source) {
                $name = basename($source, '.svg');
                $path = 'demo/'.$name.'.svg';
                Storage::disk('public')->put($path, file_get_contents($source));
                $photos[$name] = Media::firstOrCreate(['path' => $path], ['disk' => 'public', 'alt' => 'Demo illustration: '.str_replace('-', ' ', $name), 'mime' => 'image/svg+xml', 'bytes' => filesize($source)]);
            }

            $sizeSets = [
                'free' => ['Free size'], 'panjabi' => ['38', '40', '42', '44'], 'adult' => ['S', 'M', 'L', 'XL'],
                'tops' => ['M', 'L', 'XL', 'XXL'], 'kids' => ['2-3 Y', '4-5 Y', '6-7 Y', '8-9 Y'],
            ];
            // slug, English, Bangla, category, price (BDT), sale price (BDT), photo, colour EN, colour BN, size set, fabric EN, fabric BN, origin EN, origin BN, badge EN, badge BN, collections
            $products = [
                ['jamdani-red-shari', 'Dhakai Jamdani Sari, Red', 'ঢাকাই জামদানি শাড়ি (লাল)', 'shari', 8500, null, 'jamdani-red', 'Red', 'Half-silk jamdani', 'হাফ সিল্ক জামদানি', 'Narayanganj', 'নারায়ণগঞ্জ', 'Handloom', 'হ্যান্ডলুম', ['wedding-collection', 'eid-collection'], 'free'],
                ['jamdani-teal-shari', 'Dhakai Jamdani Sari, Teal', 'ঢাকাই জামদানি শাড়ি (টিল)', 'shari', 7800, 6900, 'jamdani-teal', 'Teal', 'Half-silk jamdani', 'হাফ সিল্ক জামদানি', 'Narayanganj', 'নারায়ণগঞ্জ', null, null, ['wedding-collection'], 'free'],
                ['tangail-green-shari', 'Tangail Cotton Sari, Green', 'টাঙ্গাইল তাঁতের শাড়ি (সবুজ)', 'shari', 2400, null, 'tangail-green', 'Green', 'Cotton, hand-woven', 'সুতি, তাঁতে বোনা', 'Tangail', 'টাঙ্গাইল', 'Handloom', 'তাঁতের শাড়ি', ['eid-collection'], 'free'],
                ['silk-purple-shari', 'Rajshahi Silk Sari, Purple', 'রাজশাহী সিল্ক শাড়ি (বেগুনি)', 'shari', 6200, 5500, 'silk-purple', 'Purple', 'Rajshahi silk', 'রাজশাহী সিল্ক', 'Rajshahi', 'রাজশাহী', null, null, ['wedding-collection'], 'free'],
                ['cotton-shari-daily', 'Everyday Cotton Sari', 'প্রতিদিনের সুতির শাড়ি', 'shari', 1250, null, 'cotton-shari', 'Off white', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, [], 'free'],
                ['boishakhi-shari', 'Pohela Boishakh Red-White Sari', 'পহেলা বৈশাখের লাল-সাদা শাড়ি', 'shari', 1650, 1450, 'boishakhi-shari', 'White', 'Cotton', 'সুতি', 'Tangail', 'টাঙ্গাইল', 'Boishakh Special', 'বৈশাখ স্পেশাল', ['pohela-boishakh'], 'free'],
                ['panjabi-white', 'Cotton Panjabi, White', 'সুতির পাঞ্জাবি (সাদা)', 'panjabi', 1450, null, 'panjabi-white', 'White', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, ['eid-collection'], 'panjabi'],
                ['panjabi-navy', 'Embroidered Panjabi, Navy', 'এমব্রয়ডারি পাঞ্জাবি (নেভি)', 'panjabi', 2650, 2290, 'panjabi-navy', 'Navy', 'Cotton blend', 'কটন মিক্স', 'Dhaka', 'ঢাকা', 'Eid Special', 'ঈদ স্পেশাল', ['eid-collection'], 'panjabi'],
                ['panjabi-maroon', 'Festive Panjabi, Maroon', 'উৎসবের পাঞ্জাবি (মেরুন)', 'panjabi', 2950, null, 'panjabi-maroon', 'Maroon', 'Silk blend', 'সিল্ক মিক্স', 'Dhaka', 'ঢাকা', 'Eid Special', 'ঈদ স্পেশাল', ['eid-collection', 'wedding-collection'], 'panjabi'],
                ['panjabi-eid-green', 'Eid Panjabi, Bottle Green', 'ঈদ পাঞ্জাবি (বোতল সবুজ)', 'panjabi', 1990, null, 'panjabi-eid', 'Green', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, ['eid-collection'], 'panjabi'],
                ['three-piece-pink', 'Printed Three-piece, Pink', 'প্রিন্টেড থ্রি-পিস (গোলাপি)', 'three-piece', 2250, 1890, 'kameez-pink', 'Pink', 'Lawn cotton', 'লন কটন', 'Dhaka', 'ঢাকা', null, null, ['eid-collection'], 'adult'],
                ['three-piece-blue', 'Block Print Three-piece, Blue', 'ব্লক প্রিন্ট থ্রি-পিস (নীল)', 'three-piece', 2890, null, 'kameez-blue', 'Blue', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, [], 'adult'],
                ['three-piece-yellow', 'Gaye Holud Three-piece, Yellow', 'গায়ে হলুদের থ্রি-পিস (হলুদ)', 'three-piece', 3450, null, 'kameez-yellow', 'Yellow', 'Georgette', 'জর্জেট', 'Dhaka', 'ঢাকা', 'Wedding', 'বিয়ের পোশাক', ['wedding-collection'], 'adult'],
                ['kurti-block-blue', 'Block Print Kurti', 'ব্লক প্রিন্ট কুর্তি', 'kurti', 1190, null, 'kurti-block', 'Blue', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, [], 'adult'],
                ['kurti-cotton-rust', 'Everyday Cotton Kurti', 'প্রতিদিনের সুতির কুর্তি', 'kurti', 890, 790, 'kurti-cotton', 'Rust', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, [], 'adult'],
                ['orna-red', 'Printed Orna', 'প্রিন্টেড ওড়না', 'orna-hijab', 490, null, 'orna-red', 'Red', 'Georgette', 'জর্জেট', 'Dhaka', 'ঢাকা', null, null, [], 'free'],
                ['hijab-black', 'Chiffon Hijab, Black', 'শিফন হিজাব (কালো)', 'orna-hijab', 450, null, 'hijab-black', 'Black', 'Chiffon', 'শিফন', 'Dhaka', 'ঢাকা', null, null, [], 'free'],
                ['hijab-wine', 'Embroidered Hijab, Wine', 'এমব্রয়ডারি হিজাব (ওয়াইন)', 'orna-hijab', 690, 590, 'hijab-wine', 'Wine', 'Chiffon', 'শিফন', 'Dhaka', 'ঢাকা', null, null, ['eid-collection'], 'free'],
                ['lungi-check-blue', 'Check Lungi, Blue', 'চেক লুঙ্গি (নীল)', 'lungi', 650, null, 'lungi-check', 'Blue', 'Cotton', 'সুতি', 'Narsingdi', 'নরসিংদী', null, null, [], 'free'],
                ['lungi-check-green', 'Check Lungi, Green', 'চেক লুঙ্গি (সবুজ)', 'lungi', 590, null, 'lungi-green', 'Green', 'Cotton', 'সুতি', 'Narsingdi', 'নরসিংদী', null, null, [], 'free'],
                ['shirt-sky-stripe', 'Formal Shirt, Sky Blue', 'ফরমাল শার্ট (আকাশি)', 'shirt-tshirt', 1390, null, 'shirt-blue', 'Sky blue', 'Cotton', 'সুতি', 'Gazipur', 'গাজীপুর', null, null, [], 'tops'],
                ['shirt-white-stripe', 'Casual Shirt, White', 'ক্যাজুয়াল শার্ট (সাদা)', 'shirt-tshirt', 1190, 990, 'shirt-white', 'White', 'Cotton', 'সুতি', 'Gazipur', 'গাজীপুর', null, null, [], 'tops'],
                ['tshirt-maroon', 'Cotton T-shirt, Maroon', 'কটন টি-শার্ট (মেরুন)', 'shirt-tshirt', 550, null, 'tshirt-maroon', 'Maroon', 'Cotton', 'সুতি', 'Gazipur', 'গাজীপুর', null, null, [], 'tops'],
                ['tshirt-green', 'Cotton T-shirt, Green', 'কটন টি-শার্ট (সবুজ)', 'shirt-tshirt', 550, null, 'tshirt-green', 'Green', 'Cotton', 'সুতি', 'Gazipur', 'গাজীপুর', null, null, [], 'tops'],
                ['kids-frock-pink', 'Girls Party Frock, Pink', 'মেয়েদের পার্টি ফ্রক (গোলাপি)', 'kids', 990, 850, 'frock-pink', 'Pink', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', 'Eid Special', 'ঈদ স্পেশাল', ['eid-collection'], 'kids'],
                ['kids-frock-sky', 'Girls Frock, Sky Blue', 'মেয়েদের ফ্রক (আকাশি)', 'kids', 790, null, 'frock-sky', 'Sky blue', 'Cotton', 'সুতি', 'Dhaka', 'ঢাকা', null, null, [], 'kids'],
                ['winter-shawl', 'Woollen Shawl', 'উলের শাল', 'winter', 1850, null, 'shawl-winter', 'Brown', 'Wool blend', 'উল মিক্স', 'Dhaka', 'ঢাকা', 'Winter', 'শীত', [], 'free'],
            ];
            foreach ($products as $index => [$slug, $name, $bn, $category, $price, $sale, $photo, $color, $fabric, $fabricBn, $origin, $originBn, $badge, $badgeBn, $inCollections, $sizeSet]) {
                $product = Product::firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'name_bn' => $bn, 'category_id' => $categories[$category]->id, 'brand_id' => $brand->id, 'status' => 'published', 'featured' => $index % 3 === 0 || $sale !== null,
                    'fabric' => $fabric, 'fabric_bn' => $fabricBn, 'origin' => $origin, 'origin_bn' => $originBn, 'badge' => $badge, 'badge_bn' => $badgeBn,
                    'description' => 'Comfortable for Bangladesh weather, with a good finish and fast colours. Demonstration listing: confirm actual fabric, colour and measurements before selling.',
                    'description_bn' => 'আমাদের দেশের আবহাওয়ার জন্য আরামদায়ক কাপড়, সুন্দর ফিনিশিং। এটি ডেমো তালিকা; বিক্রির আগে প্রকৃত কাপড়, রং ও মাপ নিশ্চিত করুন।',
                    'care' => "Wash gently in cold water with similar colours.\nDry in shade. Iron at medium heat.",
                    'care_bn' => "হালকা ঠান্ডা পানিতে একই রঙের কাপড়ের সাথে ধুয়ে নিন।\nছায়ায় শুকান। মাঝারি তাপে ইস্ত্রি করুন।",
                    'size_guide' => $category === 'panjabi' ? "38: chest 38 in\n40: chest 40 in\n42: chest 42 in\n44: chest 44 in\nDemo sizing; replace with actual garment measurements." : "Free size garments fit most customers. For sized items, contact us on WhatsApp or hotline for help.\nDemo text; replace with actual measurements.",
                    'size_guide_bn' => $category === 'panjabi' ? "৩৮: বুক ৩৮ ইঞ্চি\n৪০: বুক ৪০ ইঞ্চি\n৪২: বুক ৪২ ইঞ্চি\n৪৪: বুক ৪৪ ইঞ্চি\nএটি ডেমো মাপ; প্রকৃত মাপ দিয়ে পরিবর্তন করুন।" : "ফ্রি সাইজ পোশাক সাধারণত সবার জন্য মানানসই। সাইজ নিয়ে সাহায্য লাগলে হটলাইন বা হোয়াটসঅ্যাপে জানান।\nএটি ডেমো লেখা; প্রকৃত মাপ দিয়ে পরিবর্তন করুন।",
                ]);
                $product->collections()->syncWithoutDetaching(array_map(fn ($slug) => $collections[$slug]->id, $inCollections));
                if (isset($photos[$photo])) {
                    $product->media()->syncWithoutDetaching([$photos[$photo]->id => ['position' => 0]]);
                }
                foreach ($sizeSets[$sizeSet] as $size) {
                    $v = Variant::firstOrCreate(['sku' => 'MB-'.($index + 1).'-'.str_replace(' ', '', $size)], ['product_id' => $product->id, 'size' => $size, 'color' => $color, 'price' => $price * 100, 'sale_price' => $sale ? $sale * 100 : null]);
                    if ($v->wasRecentlyCreated) {
                        app(InventoryService::class)->adjust($v->id, 12, 'Development opening stock');
                    }
                }
            }

            ContentBlock::firstOrCreate(['type' => 'hero', 'position' => 0], [
                'title' => 'Eid is coming. Dress your family.', 'title_bn' => 'ঈদের সাজে পুরো পরিবার',
                'body' => 'Panjabi, three-piece, sari and kids wear at fair prices. Cash on delivery all over Bangladesh.',
                'body_bn' => 'পাঞ্জাবি, থ্রি-পিস, শাড়ি ও বাচ্চাদের পোশাক ন্যায্য দামে। সারা বাংলাদেশে ক্যাশ অন ডেলিভারি।',
                'url' => '/collection/eid-collection', 'link_label' => 'See Eid collection', 'link_label_bn' => 'ঈদ কালেকশন দেখুন', 'media_id' => $photos['hero-eid']->id ?? null,
            ]);
            ContentBlock::firstOrCreate(['type' => 'hero', 'position' => 1], [
                'title' => 'Handloom sari, made in Bangladesh', 'title_bn' => 'দেশের তাঁতের শাড়ি',
                'body' => 'Jamdani, Tangail and Rajshahi silk. Picked from real weavers.',
                'body_bn' => 'জামদানি, টাঙ্গাইল ও রাজশাহী সিল্ক। সরাসরি তাঁতিদের কাছ থেকে বাছাই করা।',
                'url' => '/category/shari', 'link_label' => 'Shop sari', 'link_label_bn' => 'শাড়ি দেখুন', 'media_id' => $photos['hero-shari']->id ?? null,
            ]);
            ContentBlock::firstOrCreate(['type' => 'promotion', 'position' => 0], [
                'title' => 'Pohela Boishakh is here', 'title_bn' => 'বৈশাখের লাল-সাদা সাজ',
                'body' => 'Red-white sari and panjabi for the Bengali new year.',
                'body_bn' => 'বাংলা নববর্ষের জন্য লাল-সাদা শাড়ি ও পাঞ্জাবি।',
                'url' => '/collection/pohela-boishakh', 'link_label' => 'Shop Boishakh collection', 'link_label_bn' => 'বৈশাখ কালেকশন', 'media_id' => $photos['boishakhi-shari']->id ?? null,
            ]);
            foreach ([
                'Shop all' => ['/shop', 'সব পোশাক'], 'Sari' => ['/category/shari', 'শাড়ি'], 'Panjabi' => ['/category/panjabi', 'পাঞ্জাবি'],
                'Three-piece' => ['/category/three-piece', 'থ্রি-পিস'], 'Kids' => ['/category/kids', 'বাচ্চাদের'], 'Eid Collection' => ['/collection/eid-collection', 'ঈদ কালেকশন'],
            ] as $title => [$url, $bn]) {
                ContentBlock::firstOrCreate(['type' => 'navigation', 'url' => $url], ['title' => $title, 'title_bn' => $bn, 'position' => ContentBlock::where('type', 'navigation')->count()]);
            }
            $pages = [
                'about' => ['About us', 'আমাদের সম্পর্কে', "Mamoni Bosraloy is a family clothing store selling sari, panjabi, three-piece and everyday wear.\n\nReplace this text with your real story before launch."],
                'contact' => ['Contact us', 'যোগাযোগ', "Call or WhatsApp +880 1700 000000, or write to hello@example.test.\n\nThese are demo contact details. Set your real details in Settings."],
                'faq' => ['Frequently asked questions', 'সাধারণ প্রশ্ন', "How do I pay?\nCash on delivery is available all over Bangladesh.\n\nHow do I choose a size?\nSee the size guide on each product, or message us on WhatsApp.\n\nCan I track my order?\nYour order page shows its status and courier tracking number."],
                'delivery' => ['Delivery information', 'ডেলিভারি তথ্য', "We deliver all over Bangladesh with cash on delivery.\nInside Dhaka 1–2 days, outside Dhaka 3–5 days.\n\nDemo text: confirm your real delivery times before launch."],
                'returns' => ['Returns & exchanges', 'রিটার্ন ও এক্সচেঞ্জ', "Request a return within 7 days from your delivered order page.\nWrong size or damaged items are replaced after our team checks them.\n\nConfirm your real return policy before launch."],
                'privacy' => ['Privacy policy', 'গোপনীয়তা নীতি', "We keep your name, phone, address and order details only to deliver your order.\nContact us for privacy requests.\n\nDemo placeholder: use a reviewed policy before launch."],
                'terms' => ['Terms of service', 'শর্তাবলি', "Prices are in BDT. COD orders are confirmed by phone or message.\nUnconfirmed orders are held for 24 hours.\n\nDemo placeholder: use reviewed terms before launch."],
            ];
            foreach ($pages as $slug => [$title,$bn,$body]) {
                Page::firstOrCreate(['slug' => $slug], ['title' => $title, 'title_bn' => $bn, 'body' => $body, 'body_bn' => 'এটি ডেমো লেখা। প্রকাশের আগে আপনার দোকানের প্রকৃত তথ্য ও নীতিমালা যোগ করুন।', 'published' => true]);
                ContentBlock::firstOrCreate(['type' => 'footer', 'url' => '/pages/'.$slug], ['title' => $title, 'title_bn' => $bn, 'position' => ContentBlock::where('type', 'footer')->count()]);
            }
            ShippingZone::firstOrCreate(['name' => 'Dhaka delivery'], ['districts' => ['Dhaka'], 'charge' => 8000]);
            ShippingZone::firstOrCreate(['name' => 'Nationwide delivery'], ['districts' => ['*'], 'charge' => 15000]);
        });
    }
}
