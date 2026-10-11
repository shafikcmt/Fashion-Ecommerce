<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ShippingZone;
use App\Models\Variant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostgresConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        if (getenv('DB_CONNECTION') !== 'pgsql' && ($_ENV['DB_CONNECTION'] ?? null) !== 'pgsql') {
            $this->markTestSkipped('Requires an isolated PostgreSQL test database. CI runs this suite on PostgreSQL.');
        }
        parent::setUp();
    }

    private function race(int $stock, bool $sameCart = false, ?string $coupon = null): array
    {
        $v = Variant::factory()->create(['stock' => $stock]);
        $zone = ShippingZone::create(['name' => 'Race test', 'districts' => ['Dhaka'], 'charge' => 0]);
        $carts = [];
        $keys = [];
        foreach ([0, 1] as $index) {
            if ($index === 1 && $sameCart) {
                $carts[] = $carts[0];
                $keys[] = $keys[0];

                continue;
            }
            $cart = Cart::create(['token' => (string) Str::uuid()]);
            $cart->items()->create(['variant_id' => $v->id, 'quantity' => 1]);
            $carts[] = $cart->id;
            $keys[] = (string) Str::uuid();
        }
        $c = config('database.connections.pgsql');
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => $c['host'], 'DB_PORT' => (string) $c['port'], 'DB_DATABASE' => $c['database'], 'DB_USERNAME' => $c['username'], 'DB_PASSWORD' => $c['password'], 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'];
        $start = (string) (microtime(true) + 1.5);
        $processes = [];
        foreach ([0, 1] as $index) {
            $p = new Process([PHP_BINARY, base_path('tests/Fixtures/checkout-worker.php'), (string) $carts[$index], (string) $zone->id, $keys[$index], $start, $coupon ?? ''], base_path(), $env, null, 20);
            $p->start();
            $processes[] = $p;
        }
        $results = [];
        foreach ($processes as $p) {
            $p->wait();
            $this->assertTrue($p->isSuccessful(), $p->getErrorOutput());
            $results[] = trim($p->getOutput());
        }

        return [$v->fresh(), $results];
    }

    public function test_two_processes_competing_for_last_item_cannot_oversell(): void
    {
        [$v,$results] = $this->race(1);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $v->reserved);
        $this->assertCount(1, array_filter($results, fn ($s) => str_starts_with($s, 'order:')));
        $this->assertContains('unavailable', $results);
    }

    public function test_simultaneous_retry_uses_one_order_and_reservation(): void
    {
        [$v,$results] = $this->race(2, true);
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $v->reserved);
    }

    public function test_simultaneous_coupon_limit_is_atomic(): void
    {
        Coupon::create(['code' => 'ONCE', 'value' => 1000, 'usage_limit' => 1, 'expires_at' => now()->addHour()]);
        [$v,$results] = $this->race(2, false, 'ONCE');
        $this->assertSame(1, Order::count());
        $this->assertSame(1, $v->reserved);
        $this->assertSame(1, (int) Coupon::first()->uses);
    }
}
