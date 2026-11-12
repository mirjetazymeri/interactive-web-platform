<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\DecryptException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.permission' => \App\Http\Middleware\AutoAuthorizePermission::class,
            'set.user.locale' => \App\Http\Middleware\SetUserLocale::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'locale.api' => \App\Http\Middleware\SetLocaleFromRequest::class,
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\Authenticate::class,
            'channel.api' => \App\Http\Middleware\ResolveApiChannel::class,
        ]);

        // if you also want locale on every request:
        // $middleware->appendToGroup('web', \App\Http\Middleware\SetUserLocale::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SetCurrentChannel::class);
        // The public website is the Next.js frontend; nothing on this host should be indexed.
        $middleware->appendToGroup('web', \App\Http\Middleware\NoIndexBackend::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\NoIndexBackend::class);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Never keep typed secrets in the session when a form fails validation.
        $exceptions->dontFlash(['notifications.mail.password', 'api_key', 'client_secret', 'token']);

        $exceptions->render(function (DecryptException $exception, Request $request) {
            if (! $request->isMethod('post') || ! $request->is('two-factor-challenge')) {
                return null;
            }

            $guard = config('fortify.guard', 'web');
            $provider = config("auth.guards.{$guard}.provider");
            $model = $provider ? config("auth.providers.{$provider}.model") : null;
            $loginId = $request->session()->get('login.id');

            if ($loginId && $model && class_exists($model)) {
                $user = $model::find($loginId);

                if ($user) {
                    // Recover accounts with stale encrypted 2FA payloads (e.g. APP_KEY changed).
                    $user->forceFill([
                        'two_factor_secret' => null,
                        'two_factor_recovery_codes' => null,
                        'two_factor_confirmed_at' => null,
                    ])->save();
                }
            }

            $request->session()->forget('login.id');

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your two-factor settings were invalid and have been reset. Please sign in and enable two-factor authentication again.',
                ]);
        });
    })
    ->create();
