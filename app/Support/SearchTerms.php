<?php

namespace App\Support;

final class SearchTerms
{
    /** Spelling variants customers use for the same garment, in English, romanised Bangla and Bangla. */
    private const GROUPS = [
        ['sari', 'shari', 'saree', 'sharee', 'শাড়ি', 'শাড়ী'],
        ['panjabi', 'punjabi', 'পাঞ্জাবি', 'পাঞ্জাবী'],
        ['three-piece', 'three piece', 'threepiece', '3 piece', '3piece', 'থ্রি-পিস', 'থ্রি পিস', 'থ্রিপিস'],
        ['kurti', 'kurta', 'কুর্তি', 'কুর্তা'],
        ['orna', 'orna', 'dupatta', 'ওড়না'],
        ['hijab', 'hizab', 'হিজাব'],
        ['lungi', 'lunggi', 'লুঙ্গি', 'লুংগি'],
        ['shirt', 'শার্ট'],
        ['t-shirt', 'tshirt', 'টি-শার্ট', 'টি শার্ট'],
        ['kids', 'kid', 'children', 'baby', 'বাচ্চা', 'বাচ্চাদের', 'শিশু'],
        ['shawl', 'chador', 'শাল', 'চাদর'],
        ['jamdani', 'jamdanee', 'জামদানি', 'জামদানী'],
        ['cotton', 'sutir', 'সুতি', 'সুতির', 'কটন'],
        ['silk', 'resham', 'সিল্ক'],
        ['eid', 'ঈদ'],
    ];

    /**
     * Expand a typed search into the typed text plus known alternative spellings.
     *
     * @return array<int, string>
     */
    public static function expand(string $query): array
    {
        $typed = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $query)));
        if ($typed === '') {
            return [];
        }
        $terms = [$typed];
        foreach (self::GROUPS as $group) {
            foreach ($group as $word) {
                if (str_contains($typed, $word)) {
                    $terms = array_merge($terms, $group);
                    break;
                }
            }
        }

        return array_values(array_unique($terms));
    }
}
