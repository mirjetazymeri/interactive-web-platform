<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoAuthorizePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Collect all permissions from the user's access levels
        $permissionsViaAccessLevels = $user->accessLevels
            ->flatMap->permissions
            ->pluck('name')
            ->unique();

        // Check if the required permission is in the user's access-level-derived permissions
        if (! $permissionsViaAccessLevels->contains($permission)) {
            abort(403, "Access denied: missing permission [{$permission}] via access level.");
        }

        return $next($request);
    }
}
