<?php

namespace App\Http\Controllers\Admin\Meetings;

use App\Enums\VideoProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meetings\MeetingSettingsRequest;
use App\Services\Meetings\Calendar\MicrosoftCalendarGateway;
use App\Services\Meetings\MeetingSettings;
use Throwable;

class MeetingSettingsController extends Controller
{
    public function __construct(private MeetingSettings $settings) {}

    public function edit(MicrosoftCalendarGateway $calendar)
    {
        $channelId = $this->channelId();
        $connection = $calendar->connection($channelId);
        $calendars = [];
        $calendarsError = null;
        if ($connection?->isConnected()) {
            try {
                $calendars = $calendar->listCalendars($connection);
            } catch (Throwable $e) {
                $calendarsError = __('meetings.calendar_list_failed');
            }
        }

        return view('admin.meetings.settings.edit', [
            'connection' => $connection,
            'calendars' => $calendars,
            'calendarsError' => $calendarsError,
            'redirectUri' => route('admin.meetings.calendar.callback'),
            'timezone' => $this->settings->timezone($channelId),
            'requireApproval' => $this->settings->bool('general.require_approval', $channelId),
            'adminRecipients' => implode(', ', $this->settings->array('general.admin_recipients', $channelId)),
            'organizerName' => (string) $this->settings->get('general.organizer_name', $channelId),
            'failClosed' => $this->settings->bool('general.fail_closed_when_calendar_unreachable', $channelId),
            'online' => $this->settings->online($channelId),
            'joiningInstructions' => $this->settings->array('online.joining_instructions', $channelId),
            'providers' => VideoProvider::cases(),
            'locales' => [$this->settings->defaultLocale(), ...$this->settings->translationLocales()],
            'calendarConnected' => (bool) $connection?->isConnected(),
        ]);
    }

    public function update(MeetingSettingsRequest $request)
    {
        $this->settings->save($request->settings(), $this->channelId());

        return redirect()->route('admin.meetings.settings.edit')->with('success', __('meetings.settings_saved'));
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
