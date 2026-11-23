<?php

namespace App\Support;

final class BdDistricts
{
    /**
     * Bangladesh's 64 districts grouped by division: English name => Bangla name.
     *
     * @var array<string, array<string, string>>
     */
    public const DIVISIONS = [
        'Dhaka' => ['Dhaka' => 'ঢাকা', 'Gazipur' => 'গাজীপুর', 'Narayanganj' => 'নারায়ণগঞ্জ', 'Narsingdi' => 'নরসিংদী', 'Manikganj' => 'মানিকগঞ্জ', 'Munshiganj' => 'মুন্সিগঞ্জ', 'Tangail' => 'টাঙ্গাইল', 'Kishoreganj' => 'কিশোরগঞ্জ', 'Faridpur' => 'ফরিদপুর', 'Gopalganj' => 'গোপালগঞ্জ', 'Madaripur' => 'মাদারীপুর', 'Rajbari' => 'রাজবাড়ী', 'Shariatpur' => 'শরীয়তপুর'],
        'Chattogram' => ['Chattogram' => 'চট্টগ্রাম', "Cox's Bazar" => 'কক্সবাজার', 'Cumilla' => 'কুমিল্লা', 'Feni' => 'ফেনী', 'Brahmanbaria' => 'ব্রাহ্মণবাড়িয়া', 'Noakhali' => 'নোয়াখালী', 'Chandpur' => 'চাঁদপুর', 'Lakshmipur' => 'লক্ষ্মীপুর', 'Rangamati' => 'রাঙ্গামাটি', 'Khagrachhari' => 'খাগড়াছড়ি', 'Bandarban' => 'বান্দরবান'],
        'Rajshahi' => ['Rajshahi' => 'রাজশাহী', 'Bogura' => 'বগুড়া', 'Pabna' => 'পাবনা', 'Sirajganj' => 'সিরাজগঞ্জ', 'Natore' => 'নাটোর', 'Naogaon' => 'নওগাঁ', 'Chapainawabganj' => 'চাঁপাইনবাবগঞ্জ', 'Joypurhat' => 'জয়পুরহাট'],
        'Khulna' => ['Khulna' => 'খুলনা', 'Jashore' => 'যশোর', 'Satkhira' => 'সাতক্ষীরা', 'Bagerhat' => 'বাগেরহাট', 'Kushtia' => 'কুষ্টিয়া', 'Jhenaidah' => 'ঝিনাইদহ', 'Magura' => 'মাগুরা', 'Narail' => 'নড়াইল', 'Chuadanga' => 'চুয়াডাঙ্গা', 'Meherpur' => 'মেহেরপুর'],
        'Barishal' => ['Barishal' => 'বরিশাল', 'Bhola' => 'ভোলা', 'Patuakhali' => 'পটুয়াখালী', 'Pirojpur' => 'পিরোজপুর', 'Jhalokathi' => 'ঝালকাঠি', 'Barguna' => 'বরগুনা'],
        'Sylhet' => ['Sylhet' => 'সিলেট', 'Moulvibazar' => 'মৌলভীবাজার', 'Habiganj' => 'হবিগঞ্জ', 'Sunamganj' => 'সুনামগঞ্জ'],
        'Rangpur' => ['Rangpur' => 'রংপুর', 'Dinajpur' => 'দিনাজপুর', 'Kurigram' => 'কুড়িগ্রাম', 'Gaibandha' => 'গাইবান্ধা', 'Nilphamari' => 'নীলফামারী', 'Lalmonirhat' => 'লালমনিরহাট', 'Panchagarh' => 'পঞ্চগড়', 'Thakurgaon' => 'ঠাকুরগাঁও'],
        'Mymensingh' => ['Mymensingh' => 'ময়মনসিংহ', 'Jamalpur' => 'জামালপুর', 'Netrokona' => 'নেত্রকোণা', 'Sherpur' => 'শেরপুর'],
    ];

    /** @return array<int, string> English district names. */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> English name => Bangla name. */
    public static function all(): array
    {
        return array_merge(...array_values(self::DIVISIONS));
    }

    /** Districts of the Dhaka division other than Dhaka itself. */
    public static function outsideDhakaCity(): array
    {
        return array_values(array_diff(array_keys(self::DIVISIONS['Dhaka']), ['Dhaka']));
    }

    public static function label(string $district): string
    {
        return app()->getLocale() === 'bn' ? (self::all()[$district] ?? $district) : $district;
    }
}
