<?php

use App\Models\Cart;
use App\Services\CheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Notification::fake();
$start = (float) $argv[4];
while (microtime(true) < $start) {
    usleep(1000);
}
try {
    $order = app(CheckoutService::class)->place(Cart::findOrFail($argv[1]), [
        'name' => 'Race test', 'email' => 'race@example.test', 'phone' => '01712345678', 'address' => 'Test Road', 'district' => 'Dhaka',
        'shipping_zone_id' => (int) $argv[2], 'payment_method' => 'cod', 'coupon' => $argv[5],
    ], $argv[3]);
    echo 'order:'.$order->id;
} catch (ValidationException $e) {
    echo 'unavailable';
}
