<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CheckRedis extends Command
{
    protected $signature = 'commerce:check-redis';

    protected $description = 'Verify the configured Redis connection without exposing connection details';

    public function handle(): int
    {
        try {
            Redis::connection()->ping();
            $this->info('Redis connection OK.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Redis is unavailable. Check configuration and service health.');

            return self::FAILURE;
        }
    }
}
