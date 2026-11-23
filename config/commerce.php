<?php

return [
    'default_locale' => env('STORE_DEFAULT_LOCALE', 'bn'),
    'media_disk' => env('MEDIA_DISK', 'public'),
    'payments' => ['bkash' => false, 'nagad' => false, 'sslcommerz' => false],
];
