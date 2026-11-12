<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\WidgetController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\LocaleController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\PrivacyController;
use App\Http\Controllers\Api\V1\StorefrontConsentController;
use App\Http\Controllers\Api\V1\StorefrontEventController;
use App\Http\Controllers\Api\V1\SeoController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\MeetingAvailabilityController;
use App\Http\Controllers\Api\V1\MeetingBookingController;
use App\Http\Controllers\Api\V1\AssistantController;

Route::prefix('v1')->middleware(['locale.api', 'channel.api'])->group(function () {

    // Localisation & settings
    Route::get('locales', [LocaleController::class, 'index']);
    Route::get('countries', [CountryController::class, 'index']);
    Route::get('countries/{code}/cities', [CountryController::class, 'cities']);
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('seo/{entity}/{slug}', [SeoController::class, 'show'])->where('slug', '.*');
    Route::get('sitemap', [\App\Http\Controllers\SitemapController::class, 'api'])->middleware('throttle:60,1');

    // GDPR / consent
    Route::get('storefront/consent', [StorefrontConsentController::class, 'show']);
    Route::post('storefront/consent', [StorefrontConsentController::class, 'store']);
    Route::delete('storefront/consent', [StorefrontConsentController::class, 'revoke']);
    Route::post('storefront/events', [StorefrontEventController::class, 'store'])->middleware('throttle:120,1');

    // Newsletter
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);

    // Contact form
    Route::post('contact', [ContactController::class, 'send'])->middleware('throttle:10,1');

    // Meetings
    Route::get('meetings/availability', [MeetingAvailabilityController::class, 'index'])->middleware('throttle:meetings-availability');
    Route::post('meetings/bookings', [MeetingBookingController::class, 'store'])->middleware('throttle:meetings-bookings');

    // Smart Assistant
    Route::get('assistant/bootstrap', [AssistantController::class, 'bootstrap'])->middleware('throttle:assistant-bootstrap');
    Route::post('assistant/message', [AssistantController::class, 'message'])->middleware('throttle:assistant-messages');

    // Auth (public)
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');


    // Pages
    Route::get('pages/{slugOrPath}', [PageController::class, 'show'])->where('slugOrPath', '.*');

    // Menus
    Route::get('menus', [MenuController::class, 'index']);
    Route::get('menus/{location}', [MenuController::class, 'show']);

    // Widgets
    Route::get('widgets/{id}/projects/{slug}', [WidgetController::class, 'project'])->whereNumber('id')->name('api.widgets.projects.show');
    Route::get('widgets/{id}', [WidgetController::class, 'show'])->whereNumber('id');
    Route::get('widgets', [WidgetController::class, 'byPlacement']);
    Route::get('widgets/home', [WidgetController::class, 'home']);
    Route::get('widgets/footer', [WidgetController::class, 'footer']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [MeController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // GDPR privacy data access/delete
        Route::get('me/privacy/export', [PrivacyController::class, 'export']);
        Route::delete('me/privacy/tracking', [PrivacyController::class, 'destroyTracking']);
    });
});
