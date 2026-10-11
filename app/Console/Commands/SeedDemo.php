<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class SeedDemo extends Command
{
    protected $signature = 'commerce:seed-demo';

    protected $description = 'Seed development catalog and content; forbidden in production';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Demo data is forbidden in production.');

            return self::FAILURE;
        }
        $this->call('db:seed', ['--class' => DemoSeeder::class]);

        return self::SUCCESS;
    }
}
