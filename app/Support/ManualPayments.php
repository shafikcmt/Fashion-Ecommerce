<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Manual mobile-wallet payments: the customer sends money to the store's own bKash/Nagad number
 * and enters the sender number and transaction ID. No provider API is involved, so the order stays
 * unpaid until a staff member verifies the transfer in the admin panel.
 */
final class ManualPayments
{
    public const METHODS = [
        'bkash_manual' => ['label' => 'bKash', 'setting' => 'bkash_number'],
        'nagad_manual' => ['label' => 'Nagad', 'setting' => 'nagad_number'],
    ];

    /** @return array<string, array{label: string, number: string}> methods the store has configured a number for */
    public static function available(): array
    {
        $available = [];
        foreach (self::METHODS as $method => $config) {
            $number = trim(Setting::value($config['setting']));
            if (preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', preg_replace('/[\s-]+/', '', $number))) {
                $available[$method] = ['label' => $config['label'], 'number' => $number];
            }
        }

        return $available;
    }

    public static function isManual(string $method): bool
    {
        return isset(self::METHODS[$method]);
    }

    public static function label(string $method): string
    {
        return $method === 'cod' ? __('Cash on delivery') : (self::METHODS[$method]['label'] ?? $method);
    }
}
