<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar definition for the admin panel.
 *
 * Every link points at an existing named route. `active` lists the route-name patterns
 * that highlight an item; groups open automatically when one of their children is active.
 *
 * Intentionally absent:
 * - a standalone "Widgets" entry: widgets are managed inside Pages (Pages → Widget library);
 * - "Employees": hidden from navigation only — its routes, model and data are untouched;
 * - "SEO & Tracking": reached through System Configs → All Configs (Settings Center).
 */
class AdminNavigation
{
    public static function sections(): array
    {
        return [
            [
                'heading' => 'Main',
                'items' => [
                    ['label' => 'Dashboard', 'icon' => 'ri-dashboard-3-line', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard']],
                    [
                        'label' => 'Pages', 'icon' => 'ri-pages-line',
                        'children' => [
                            ['label' => 'All pages', 'route' => 'admin.pages.index', 'active' => ['admin.pages.*']],
                            ['label' => 'Widget library', 'route' => 'admin.widgets.index', 'active' => ['admin.widgets.*']],
                        ],
                    ],
                    ['label' => 'Newsletters', 'icon' => 'ri-mail-send-line', 'route' => 'admin.newsletters.index', 'active' => ['admin.newsletters.*']],
                    [
                        'label' => __('meetings.meetings'), 'icon' => 'ri-calendar-event-line',
                        'children' => [
                            ['label' => __('meetings.bookings'), 'route' => 'admin.meetings.bookings.index', 'active' => ['admin.meetings.bookings.*']],
                            ['label' => __('meetings.meeting_types'), 'route' => 'admin.meetings.types.index', 'active' => ['admin.meetings.types.*']],
                            ['label' => __('meetings.office_locations'), 'route' => 'admin.meetings.locations.index', 'active' => ['admin.meetings.locations.*']],
                            ['label' => __('meetings.availability'), 'route' => 'admin.meetings.availability.index', 'active' => ['admin.meetings.availability.*']],
                            ['label' => __('meetings.settings'), 'route' => 'admin.meetings.settings.edit', 'active' => ['admin.meetings.settings.*', 'admin.meetings.calendar.*']],
                        ],
                    ],
                    [
                        'label' => __('assistant.assistant'), 'icon' => 'ri-robot-2-line',
                        'children' => [
                            ['label' => __('assistant.settings'), 'route' => 'admin.assistant.settings.edit', 'active' => ['admin.assistant.settings.*']],
                            ['label' => __('assistant.questions'), 'route' => 'admin.assistant.questions.index', 'active' => ['admin.assistant.questions.*']],
                            ['label' => __('assistant.categories'), 'route' => 'admin.assistant.categories.index', 'active' => ['admin.assistant.categories.*']],
                            ['label' => __('assistant.suggestions'), 'route' => 'admin.assistant.suggestions.index', 'active' => ['admin.assistant.suggestions.*']],
                            ['label' => __('assistant.synonyms'), 'route' => 'admin.assistant.synonyms.index', 'active' => ['admin.assistant.synonyms.*']],
                            ['label' => __('assistant.test'), 'route' => 'admin.assistant.test.show', 'active' => ['admin.assistant.test.*']],
                            ['label' => __('assistant.unanswered'), 'route' => 'admin.assistant.unanswered.index', 'active' => ['admin.assistant.unanswered.*']],
                            ['label' => __('assistant.knowledge'), 'route' => 'admin.assistant.knowledge.edit', 'active' => ['admin.assistant.knowledge.*']],
                            ['label' => __('assistant.ai_integration'), 'route' => 'admin.assistant.ai.edit', 'active' => ['admin.assistant.ai.*']],
                        ],
                    ],
                ],
            ],
            [
                'heading' => 'Administration',
                'items' => [
                    [
                        'label' => 'Users', 'icon' => 'ri-user-line',
                        'children' => [
                            ['label' => 'All users', 'route' => 'admin.users.index', 'active' => ['admin.users.index', 'admin.users.edit', 'admin.users.delete', 'admin.users.disable-2fa']],
                            ['label' => 'Create user', 'route' => 'admin.users.create', 'active' => ['admin.users.create']],
                        ],
                    ],
                ],
            ],
            [
                'heading' => 'System settings',
                'items' => [
                    [
                        'label' => 'Access Control', 'icon' => 'ri-shield-user-line',
                        'children' => [
                            ['label' => 'Roles', 'route' => 'admin.roles.index', 'active' => ['admin.roles.*']],
                            ['label' => 'Modules', 'route' => 'admin.modules.index', 'active' => ['admin.modules.*']],
                            ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'active' => ['admin.permissions.*']],
                            ['label' => 'Access Levels', 'route' => 'admin.access_levels.index', 'active' => ['admin.access_levels.*']],
                        ],
                    ],
                    [
                        'label' => 'Localization', 'icon' => 'ri-translate-2',
                        'children' => [
                            ['label' => 'Translations', 'route' => 'admin.translations.index', 'active' => ['admin.translations.*']],
                            ['label' => 'Locales', 'route' => 'admin.locales.index', 'active' => ['admin.locales.*']],
                        ],
                    ],
                    [
                        'label' => 'System Configs', 'icon' => 'ri-settings-3-line',
                        'children' => [
                            ['label' => 'Countries & Cities', 'route' => 'admin.countries.index', 'active' => ['admin.countries.*']],
                            ['label' => 'Channels', 'route' => 'admin.channels.index', 'active' => ['admin.channels.*']],
                            ['label' => 'All Configs', 'route' => 'admin.settings.index', 'active' => ['admin.settings.*', 'admin.seo.*']],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Resolved sections: URLs filled in, active flags computed, links to missing routes dropped.
     */
    public static function resolve(?Request $request = null): array
    {
        $request ??= request();

        $resolveItem = function (array $item) use (&$resolveItem, $request): ?array {
            if (!empty($item['children'])) {
                $item['children'] = array_values(array_filter(array_map($resolveItem, $item['children'])));
                if ($item['children'] === []) {
                    return null;
                }
                $item['active'] = collect($item['children'])->contains('active', true);
                $item['url'] = null;

                return $item;
            }

            if (!Route::has($item['route'])) {
                return null;
            }

            $item['url'] = route($item['route']);
            $item['active'] = $request->routeIs(...($item['active'] ?? [$item['route']]));

            return $item;
        };

        return collect(self::sections())
            ->map(function (array $section) use ($resolveItem) {
                $section['items'] = array_values(array_filter(array_map($resolveItem, $section['items'])));

                return $section;
            })
            ->filter(fn (array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }
}
