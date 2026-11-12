<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Illuminate\Support\Facades\Auth;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureTwoFactorEncrypter();

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // 🔐 View bindings for Blade-based auth
        Fortify::loginView(fn () => view('admin.auth.login'));
        Fortify::registerView(fn () => view('admin.auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('admin.auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('admin.auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('admin.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('admin.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('admin.auth.confirm-password'));


        // 🔐 Rate limiters
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // ➕ Role-based login redirect
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    $user = Auth::user();

                    if ($user && $user->hasRole('Admin')) {
                        return redirect()->route('admin.dashboard');
                    }

                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()
                        ->route('login')
                        ->withErrors(['email' => 'Your account does not have admin access.']);
                }
            };
        });

    }

    private function configureTwoFactorEncrypter(): void
    {
        $rawKey = config('fortify.two_factor_key');

        if (empty($rawKey)) {
            return;
        }

        $cipher = config('app.cipher', 'AES-256-CBC');

        $encrypter = new Encrypter($this->parseEncryptionKey($rawKey), $cipher);

        $previousKeys = array_map(
            fn (string $key): string => $this->parseEncryptionKey($key),
            config('fortify.two_factor_previous_keys', [])
        );

        if (! empty($previousKeys)) {
            $encrypter->previousKeys($previousKeys);
        }

        Fortify::encryptUsing($encrypter);
    }

    private function parseEncryptionKey(string $key): string
    {
        $prefix = 'base64:';

        if (Str::startsWith($key, $prefix)) {
            return base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

}
