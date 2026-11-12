<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Channel;

class ResolveApiChannel
{
    public function handle($request, Closure $next)
    {
        $channel = null;

        // 1. query
        if ($slug = $request->query('channel')) {
            $channel = Channel::where('code', $slug)->first();
        }

        // 2. header
        if (!$channel && $slug = $request->header('X-Channel')) {
            $channel = Channel::where('code', $slug)->first();
        }

        // 3. authenticated user
        if (!$channel && auth()->check()) {
            $channel = auth()->user()->currentChannel;
        }

        // 4. fallback
        if (!$channel) {
            $channel = Channel::where('code', 'default')->first();
        }

        // ❗ important
        app()->instance('currentChannel', $channel);

        // optional
        $request->attributes->set('channel_id', $channel?->id);

        return $next($request);
    }
}
