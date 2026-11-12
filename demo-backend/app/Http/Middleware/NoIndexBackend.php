<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The CMS host (admin panel, API, Blade storefront preview) is never the
 * public website: tell search engines not to index anything it serves.
 * Uploaded media under /storage is served by the web server directly and
 * stays reachable for link previews.
 */
class NoIndexBackend
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $response->headers->has('X-Robots-Tag')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
