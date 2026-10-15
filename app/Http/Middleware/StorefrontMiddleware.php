<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorefrontMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(in_array(session('locale'), ['en', 'bn'], true) ? session('locale') : 'en');
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if ($request->is('account*', 'orders*', 'checkout*', 'admin*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
