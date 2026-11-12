<?php

/*
| Meetings defaults. Saved values live in the settings table under the
| "meetings." prefix (per channel, private: they are not part of
| config/settings.php, so the public settings API never exposes them).
*/

return [

    'settings' => [
        'general' => [
            // Null falls back to config('app.timezone').
            'timezone' => null,
            'require_approval' => false,
            'admin_recipients' => [],
            'organizer_name' => '',
            'fail_closed_when_calendar_unreachable' => true,
        ],
        'online' => [
            'provider' => 'teams',
            'auto_create' => true,
            // Fixed meeting URLs keyed by provider, e.g. a Zoom personal room.
            'static_urls' => [],
            'allow_manual_after_booking' => true,
            // Keyed by locale.
            'joining_instructions' => [],
        ],
    ],

    'microsoft' => [
        // Seconds to wait before re-reading an event whose Teams link Microsoft is still creating.
        'teams_poll_seconds' => [1, 2, 3],
    ],

    'type_defaults' => [
        'duration_minutes' => 30,
        'slot_interval_minutes' => 30,
        'min_notice_minutes' => 240,
        'max_advance_days' => 60,
    ],

];
