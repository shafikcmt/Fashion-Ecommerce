<?php

namespace App\Providers;

use App\Models;
use App\Observers\AuditObserver;
use App\Policies\CommercePolicy;
use App\Policies\OrderPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        foreach ([
            Models\Product::class => 'catalog_manager', Models\Variant::class => 'catalog_manager',
            Models\Category::class => 'catalog_manager', Models\Collection::class => 'catalog_manager', Models\Brand::class => 'catalog_manager', Models\Tag::class => 'catalog_manager',
            Models\Media::class => ['catalog_manager', 'content_editor'], Models\Page::class => 'content_editor', Models\ContentBlock::class => 'content_editor', Models\Setting::class => 'content_editor',
            Models\Coupon::class => 'order_manager', Models\ShippingZone::class => 'order_manager', Models\ReturnRequest::class => 'order_manager',
            Models\User::class => 'super_admin', Models\StockMovement::class => 'catalog_manager', Models\AuditLog::class => 'super_admin',
        ] as $model => $role) {
            $policy = 'policy.'.$model;
            $this->app->singleton($policy, fn () => new CommercePolicy($role, in_array($model, [Models\StockMovement::class, Models\AuditLog::class], true)));
            Gate::policy($model, $policy);
            if ($model !== Models\AuditLog::class) {
                $model::observe(AuditObserver::class);
            }
        }
        Gate::policy(Models\Order::class, OrderPolicy::class);
        Models\Order::observe(AuditObserver::class);
        Gate::define('catalog-import', fn (Models\User $u) => $u->hasVerifiedEmail() && in_array($u->role, ['super_admin', 'catalog_manager'], true));
        Gate::define('reports', fn (Models\User $u) => $u->hasVerifiedEmail() && in_array($u->role, ['super_admin', 'order_manager'], true));
        RateLimiter::for('auth', fn (Request $r) => [Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()), Limit::perMinute(20)->by($r->ip())]);
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?? $r->ip()));
    }
}
