<?php

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('commerce:expire-reservations', function () {
    $count = 0;
    Order::where('status', 'pending')->where('reservation_expires_at', '<=', now())->eachById(function ($order) use (&$count) {
        if (app(OrderService::class)->expire($order)) {
            $count++;
        }
    });
    $this->info("$count expired reservations released.");
})->purpose('Release expired pending COD reservations');
Schedule::command('commerce:expire-reservations')->everyMinute()->withoutOverlapping();
