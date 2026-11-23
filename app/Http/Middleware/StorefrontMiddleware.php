<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorefrontMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = ['en', 'bn'];
        if ($this->isAdminRequest($request)) {
            app()->setLocale(in_array(session('admin_locale'), $locales, true) ? session('admin_locale') : 'en');
        } else {
            app()->setLocale(in_array(session('locale'), $locales, true) ? session('locale') : config('commerce.default_locale'));
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if ($request->is('account*', 'orders*', 'checkout*', 'admin*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /** The admin panel keeps its own language (English unless switched); Livewire calls are matched by their referer. */
    private function isAdminRequest(Request $request): bool
    {
        if ($request->is('admin', 'admin/*')) {
            return true;
        }

        return $request->is('livewire*') && str_starts_with((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), '/admin');
    }
}
