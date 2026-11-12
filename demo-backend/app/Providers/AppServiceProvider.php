<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use App\Models\Locale;
use App\Services\SettingService;
use App\Models\Channel;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\StorefrontComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Stack traces leave out call arguments (PHP's production default): they can hold
        // passwords, tokens or personal data, truncated beyond what log redaction can match.
        ini_set('zend.exception_ignore_args', '1');

        $this->app->singleton(SettingService::class, function () {
            return new SettingService();
        });

        $this->app->bind(
            \App\Services\Meetings\Calendar\CalendarGateway::class,
            \App\Services\Meetings\Calendar\MicrosoftCalendarGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Meetings: separate counters per endpoint. Plain "throttle:N,1" shares one counter per IP
        // across every route, so browsing times would use up the booking allowance.
        \Illuminate\Support\Facades\RateLimiter::for('meetings-availability', fn (\Illuminate\Http\Request $request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('meetings-availability|'.$request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('meetings-bookings', fn (\Illuminate\Http\Request $request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('meetings-bookings|'.$request->ip()));

        // Smart Assistant: per-IP limits, keyed on the connection address. Forwarded-for headers are
        // only honoured for proxies configured as trusted, so clients cannot pick their own key.
        \Illuminate\Support\Facades\RateLimiter::for('assistant-messages', fn (\Illuminate\Http\Request $request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute((int) config('assistant.rate_limits.message', 20))->by('assistant-messages|'.$request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('assistant-bootstrap', fn (\Illuminate\Http\Request $request) =>
            \Illuminate\Cache\RateLimiting\Limit::perMinute((int) config('assistant.rate_limits.bootstrap', 60))->by('assistant-bootstrap|'.$request->ip()));
        // Behind the Next.js server or a reverse proxy: honour the forwarded visitor address only from
        // the addresses listed in TRUSTED_PROXIES (none by default), so per-visitor limits stay per visitor.
        if ($proxies = array_values(array_filter(array_map('trim', explode(',', (string) config('app.trusted_proxies')))))) {
            \Illuminate\Http\Middleware\TrustProxies::at($proxies);
        }
        // Content edits reach the assistant's cached index on the next question.
        \App\Services\Assistant\AssistantIndexRepository::watchModels();

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Hydrate config('locales') from database
        |--------------------------------------------------------------------------
        | Keeps Blade logic untouched.
        | Falls back to config/locales.php if DB empty.
        */

        if (!app()->runningInConsole()) {

            try {

                $locales = Cache::remember('locales.active', 3600, function () {
                    return Locale::where('is_active', true)->get();
                });

                if ($locales->isNotEmpty()) {

                    $formatted = $locales
                        ->keyBy('code')
                        ->map(fn ($locale) => [
                            'name' => $locale->name,
                            'flag' => $locale->flag,
                        ])
                        ->toArray();

                    // Override config
                    config(['locales' => $formatted]);

                    // Update supported locales for middleware
                    config(['app.supported_locales' => array_keys($formatted)]);

                    // Update default locale dynamically
                    $default = $locales->firstWhere('is_default', true)?->code;
                    if ($default) {
                        config(['app.locale' => $default]);
                    }
                }

            } catch (\Throwable $e) {
                // Silent fallback to config file
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Keep your original view composer
        |--------------------------------------------------------------------------
        */

        view()->composer('*', function ($view) {
            $view->with('availableLocales', config('locales'));
            if (auth()->check()) {
                $view->with('userChannels', auth()->user()->channels);
                $view->with('currentChannel', auth()->user()->currentChannel);
            }
        });

        view()->composer('storefront.*', StorefrontComposer::class);
        view()->composer(
            ['layouts.master', 'layouts.components.admin-sidebar', 'layouts.components.admin-topbar'],
            AdminLayoutComposer::class
        );
    }
}
