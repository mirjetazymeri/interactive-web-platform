<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SitemapController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\AccessLevelController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\NewsletterSubscriberController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\Seo\SeoSettingsController;
use App\Http\Controllers\Admin\Seo\TrackingSettingsController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\Meetings\AvailabilityController;
use App\Http\Controllers\Admin\Meetings\BookingController;
use App\Http\Controllers\Admin\Meetings\CalendarConnectionController;
use App\Http\Controllers\Admin\Meetings\MeetingSettingsController;
use App\Http\Controllers\Admin\Meetings\MeetingTypeController;
use App\Http\Controllers\Admin\Meetings\OfficeLocationController;
use App\Http\Controllers\Admin\Assistant\AssistantAiIntegrationController;
use App\Http\Controllers\Admin\Assistant\AssistantCategoryController;
use App\Http\Controllers\Admin\Assistant\AssistantKnowledgeController;
use App\Http\Controllers\Admin\Assistant\AssistantQuestionController;
use App\Http\Controllers\Admin\Assistant\AssistantSettingsController;
use App\Http\Controllers\Admin\Assistant\AssistantSuggestionController;
use App\Http\Controllers\Admin\Assistant\AssistantSynonymController;
use App\Http\Controllers\Admin\Assistant\AssistantTestController;
use App\Http\Controllers\Admin\Assistant\AssistantUnansweredController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Storefront\PageController as StorefrontPageController;


Route::get('/', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login');
    }
    return redirect()->route('admin.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'create'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'store'])->name('auth.google.callback');
});

Route::middleware(['locale.api', 'channel.api'])->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index']);
    Route::get('/sitemaps/pages.xml', [SitemapController::class, 'pages']);
});

/*
|--------------------------------------------------------------------------
| Storefront (SSR Blade — settings-driven layout prototype)
|--------------------------------------------------------------------------
*/
Route::middleware(['locale.api', 'channel.api'])
    ->name('storefront.')
    ->group(function () {
        Route::get('/storefront', [StorefrontPageController::class, 'home'])->name('home');
        Route::get('/storefront/projects/{widgetId}/{slug}', [\App\Http\Controllers\Storefront\ProjectController::class, 'show'])->whereNumber('widgetId')->name('projects.show');
        Route::get('/storefront/{slugOrPath}', [StorefrontPageController::class, 'show'])
            ->where('slugOrPath', '.*')
            ->name('page');
    });

Route::middleware(['auth', 'verified', 'set.user.locale', 'role:Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/security', 'admin.security')->name('security');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('profile', [ProfileController::class, 'updateInfo'])->name('profile.update-info');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
        Route::post('profile/two-factor', [ProfileController::class, 'twoFactorEnable'])->name('profile.2fa.enable');
        Route::post('profile/two-factor/confirm', [ProfileController::class, 'twoFactorConfirm'])->name('profile.2fa.confirm');
        Route::delete('profile/two-factor', [ProfileController::class, 'twoFactorDisable'])->name('profile.2fa.disable');
        Route::post('profile/two-factor/recovery-codes', [ProfileController::class, 'twoFactorRegenerateCodes'])->name('profile.2fa.recovery-codes');

        // Access Levels
        Route::resource('access_levels', AccessLevelController::class);
        // Modules
        Route::resource('modules', ModuleController::class);
        // Permissions
        Route::resource('permissions', PermissionController::class);
        // Roles
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::match(['get', 'post'], 'roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::match(['get', 'post'], 'roles/edit/{id}', [RoleController::class, 'edit'])->name('roles.edit');
        Route::get('roles/delete/{id}', [RoleController::class, 'delete'])->name('roles.delete');
        Route::post('roles/attach-permissions/{id}', [RoleController::class, 'attachPermissions'])->name('roles.attach.permissions');

        // Channels
        Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
        Route::match(['get', 'post'], 'channels/create', [ChannelController::class, 'create'])->name('channels.create');
        Route::match(['get', 'post'], 'channels/edit/{id}', [ChannelController::class, 'edit'])->name('channels.edit');
        Route::get('channels/delete/{id}', [ChannelController::class, 'delete'])->name('channels.delete');
        Route::get('channels/switch/{channel}', [ChannelController::class, 'switch'])->name('channels.switch');

        // Countries & Cities
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::get('countries/create', [CountryController::class, 'create'])->name('countries.create');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::get('countries/edit/{id}', [CountryController::class, 'edit'])->name('countries.edit');
        Route::put('countries/{id}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{id}', [CountryController::class, 'delete'])->name('countries.delete');
        Route::post('countries/{countryId}/cities', [CountryController::class, 'storeCity'])->name('countries.cities.store');
        Route::put('countries/{countryId}/cities/{cityId}', [CountryController::class, 'updateCity'])->name('countries.cities.update');
        Route::delete('countries/{countryId}/cities/{cityId}', [CountryController::class, 'deleteCity'])->name('countries.cities.delete');

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::get('settings/layout-preview', [SettingController::class, 'layoutPreview'])->name('settings.layout-preview');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email');

        // SEO & Tracking
        Route::get('seo/settings', [SeoSettingsController::class, 'edit'])->name('seo.settings.edit');
        Route::put('seo/settings', [SeoSettingsController::class, 'update'])->name('seo.settings.update');
        Route::get('seo/tracking', [TrackingSettingsController::class, 'edit'])->name('seo.tracking.edit');
        Route::put('seo/tracking', [TrackingSettingsController::class, 'update'])->name('seo.tracking.update');

        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::match(['get', 'post'], 'users/create', [UserController::class, 'create'])->name('users.create');
        Route::match(['get', 'post'], 'users/edit/{id}', [UserController::class, 'edit'])->name('users.edit');
        Route::get('users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');
        Route::post('users/{id}/disable-2fa', [UserController::class, 'disableTwoFactor'])->name('users.disable-2fa');

        // Locales
        Route::resource('locales', LocaleController::class);
        Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

        // Translations
        Route::get('translations', [TranslationController::class, 'index'])->name('translations.index');
        Route::post('translations', [TranslationController::class, 'store'])->name('translations.store');
        Route::post('translations/update', [TranslationController::class, 'update'])->name('translations.update');
        Route::delete('translations/{key}', [TranslationController::class, 'destroy'])->name('translations.destroy');

        // Employees
        Route::resource('employees', EmployeeController::class);

        // Media Library
        Route::get('media', [MediaLibraryController::class, 'index'])->name('media.index');
        Route::get('media/create', [MediaLibraryController::class, 'create'])->name('media.create');
        Route::post('media', [MediaLibraryController::class, 'store'])->name('media.store');
        Route::post('media/{mediaFolder}/upload', [MediaLibraryController::class, 'upload'])->name('media.upload');
        Route::post('media/{mediaFolder}/reorder', [MediaLibraryController::class, 'reorder'])->name('media.reorder');
        Route::get('media/{mediaFolder}/edit', [MediaLibraryController::class, 'edit'])->name('media.edit');
        Route::put('media/{mediaFolder}', [MediaLibraryController::class, 'update'])->name('media.update');
        Route::delete('media/{mediaFolder}', [MediaLibraryController::class, 'destroy'])->name('media.destroy');
        Route::get('media/{mediaFolder}', [MediaLibraryController::class, 'show'])->name('media.show');
        Route::patch('media/items/{media}', [MediaLibraryController::class, 'updateMedia'])->name('media.items.update');
        Route::delete('media/items/{media}', [MediaLibraryController::class, 'destroyMedia'])->name('media.items.destroy');
        Route::post('media/items/{media}/move', [MediaLibraryController::class, 'moveMedia'])->name('media.items.move');

        // Newsletter Subscribers
        Route::get('newsletters', [NewsletterSubscriberController::class, 'index'])->name('newsletters.index');
        Route::get('newsletters/create', [NewsletterSubscriberController::class, 'create'])->name('newsletters.create');
        Route::post('newsletters', [NewsletterSubscriberController::class, 'store'])->name('newsletters.store');
        Route::get('newsletters/edit/{id}', [NewsletterSubscriberController::class, 'edit'])->name('newsletters.edit');
        Route::put('newsletters/{id}', [NewsletterSubscriberController::class, 'update'])->name('newsletters.update');
        Route::delete('newsletters/{id}', [NewsletterSubscriberController::class, 'delete'])->name('newsletters.delete');

        // Meetings
        Route::prefix('meetings')->name('meetings.')->group(function () {
            Route::resource('types', MeetingTypeController::class)->except(['show'])->whereNumber('type');
            Route::resource('locations', OfficeLocationController::class)->except(['show'])->whereNumber('location');
            Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
            Route::prefix('bookings/{booking}')->whereNumber('booking')->name('bookings.')->group(function () {
                Route::get('/', [BookingController::class, 'show'])->name('show');
                Route::post('confirm', [BookingController::class, 'confirm'])->name('confirm');
                Route::post('format', [BookingController::class, 'changeFormat'])->name('format');
                Route::post('reschedule', [BookingController::class, 'reschedule'])->name('reschedule');
                Route::post('cancel', [BookingController::class, 'cancel'])->name('cancel');
                Route::post('video-link', [BookingController::class, 'setVideoLink'])->name('video-link');
                Route::post('resend', [BookingController::class, 'resend'])->name('resend');
                Route::post('calendar-sync', [BookingController::class, 'retrySync'])->name('calendar-sync');
            });
            Route::get('availability', [AvailabilityController::class, 'index'])->name('availability.index');
            Route::put('availability', [AvailabilityController::class, 'update'])->name('availability.update');
            Route::post('availability/exceptions', [AvailabilityController::class, 'storeException'])->name('availability.exceptions.store');
            Route::delete('availability/exceptions/{exception}', [AvailabilityController::class, 'destroyException'])->whereNumber('exception')->name('availability.exceptions.destroy');
            Route::get('calendar/connect', [CalendarConnectionController::class, 'connect'])->name('calendar.connect');
            Route::get('calendar/callback', [CalendarConnectionController::class, 'callback'])->name('calendar.callback');
            Route::put('calendar', [CalendarConnectionController::class, 'select'])->name('calendar.select');
            Route::delete('calendar', [CalendarConnectionController::class, 'disconnect'])->name('calendar.disconnect');
            Route::get('settings', [MeetingSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [MeetingSettingsController::class, 'update'])->name('settings.update');
        });

        // Smart Assistant
        Route::prefix('assistant')->name('assistant.')->group(function () {
            Route::get('settings', [AssistantSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [AssistantSettingsController::class, 'update'])->name('settings.update');
            Route::post('questions/reorder', [AssistantQuestionController::class, 'reorder'])->name('questions.reorder');
            Route::resource('questions', AssistantQuestionController::class)->except(['show'])->whereNumber('question');
            Route::resource('categories', AssistantCategoryController::class)->except(['show'])->whereNumber('category');
            Route::resource('synonyms', AssistantSynonymController::class)->except(['show', 'create'])->whereNumber('synonym');
            Route::get('suggestions', [AssistantSuggestionController::class, 'index'])->name('suggestions.index');
            Route::post('suggestions', [AssistantSuggestionController::class, 'store'])->name('suggestions.store');
            Route::post('suggestions/reorder', [AssistantSuggestionController::class, 'reorder'])->name('suggestions.reorder');
            Route::delete('suggestions/{suggestion}', [AssistantSuggestionController::class, 'destroy'])->whereNumber('suggestion')->name('suggestions.destroy');
            Route::get('test', [AssistantTestController::class, 'show'])->name('test.show');
            Route::post('test', [AssistantTestController::class, 'run'])->name('test.run');
            Route::get('knowledge', [AssistantKnowledgeController::class, 'edit'])->name('knowledge.edit');
            Route::put('knowledge', [AssistantKnowledgeController::class, 'update'])->name('knowledge.update');
            Route::get('ai-integration', [AssistantAiIntegrationController::class, 'edit'])->name('ai.edit');
            Route::put('ai-integration', [AssistantAiIntegrationController::class, 'update'])->name('ai.update');
            Route::get('unanswered', [AssistantUnansweredController::class, 'index'])->name('unanswered.index');
            Route::prefix('unanswered/{question}')->whereNumber('question')->name('unanswered.')->group(function () {
                Route::post('status', [AssistantUnansweredController::class, 'status'])->name('status');
                Route::post('attach', [AssistantUnansweredController::class, 'attach'])->name('attach');
                Route::delete('/', [AssistantUnansweredController::class, 'destroy'])->name('destroy');
            });
        });

        // Pages (with Page Builder)
        Route::resource('pages', PageController::class);
        Route::post('pages/{page}/widgets/attach', [PageController::class, 'attachWidget'])->name('pages.widgets.attach');
        Route::patch('pages/{page}/widgets/{pivotId}/config', [PageController::class, 'updatePlacement'])->name('pages.widgets.config');
        Route::delete('pages/{page}/widgets/{pivotId}', [PageController::class, 'detachWidget'])->name('pages.widgets.detach');
        Route::post('pages/{page}/widgets/reorder', [PageController::class, 'reorderWidgets'])->name('pages.widgets.reorder');
        Route::post('pages/{page}/widgets/{pivotId}/toggle', [PageController::class, 'toggleWidget'])->name('pages.widgets.toggle');
        Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');


        // Widgets
        Route::post('widgets/reorder', [WidgetController::class, 'reorder'])->name('widgets.reorder');
        Route::resource('widgets', WidgetController::class)->except(['show']);
    });




