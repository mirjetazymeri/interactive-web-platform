<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetUserLocale
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->locale) {
            app()->setLocale(auth()->user()->locale);
        } elseif (session()->has('locale')) {
            app()->setLocale(session('locale'));
        }

        return $next($request);
    }
}
