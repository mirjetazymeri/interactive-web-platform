<?php

namespace App\Http\Middleware;

use Closure;

class SetCurrentChannel
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 🔥 STEP 1: If no channel selected → auto assign first
        if (!$user->current_channel_id) {
            $firstChannel = $user->channels()->first();

            if ($firstChannel) {
                $user->update([
                    'current_channel_id' => $firstChannel->id
                ]);

                $user->refresh();
            }
        }

        // 🔥 STEP 2: Ensure user STILL has access
        if (
            $user->current_channel_id &&
            !$user->channels()->where('channel_id', $user->current_channel_id)->exists()
        ) {
            $fallback = $user->channels()->first();

            $user->update([
                'current_channel_id' => $fallback?->id
            ]);

            $user->refresh();
        }

        // 🔥 STEP 3: Bind globally
        app()->instance('currentChannel', $user->currentChannel);

        return $next($request);
    }
}
