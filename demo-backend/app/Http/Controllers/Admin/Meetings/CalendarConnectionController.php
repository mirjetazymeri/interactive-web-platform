<?php

namespace App\Http\Controllers\Admin\Meetings;

use App\Http\Controllers\Controller;
use App\Models\MeetingCalendarConnection;
use App\Services\Meetings\Calendar\MicrosoftCalendarGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Connects the channel's host Microsoft 365 calendar (a work account) with
 * offline access, so the site can read busy times, write booking events and
 * create Teams meeting links.
 */
class CalendarConnectionController extends Controller
{
    private const STATE_KEY = 'meetings.microsoft_oauth_state';

    public function __construct(private MicrosoftCalendarGateway $calendar) {}

    public function connect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put(self::STATE_KEY, $state);

        return redirect()->away($this->calendar->authorizeUrl($state, $this->redirectUri()));
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull(self::STATE_KEY);

        if ($request->filled('error')) {
            return $this->back('error', __('meetings.calendar_denied'));
        }
        if (! $expected || ! hash_equals($expected, (string) $request->query('state')) || ! $request->filled('code')) {
            return $this->back('error', __('meetings.calendar_connect_failed'));
        }

        try {
            $tokens = $this->calendar->exchangeCode((string) $request->query('code'), $this->redirectUri());
            $email = $tokens['refresh_token'] && $this->hasCalendarScope($tokens['scope']) ? $this->calendar->accountEmail($tokens['access_token']) : null;
        } catch (Throwable $e) {
            Log::warning('[Meetings] Microsoft 365 connect failed: '.$e->getMessage());

            return $this->back('error', __('meetings.calendar_connect_failed'));
        }

        if (! $email) {
            return $this->back('error', __('meetings.calendar_scope_missing'));
        }

        $channelId = $this->channelId();
        $existing = MeetingCalendarConnection::query()->where('channel_id', $channelId)->first();

        MeetingCalendarConnection::query()->updateOrCreate(['channel_id' => $channelId], [
            'provider' => 'microsoft',
            'account_email' => $email,
            // Keep the chosen calendar when the same account reconnects.
            'calendar_id' => $existing && $existing->account_email === $email ? $existing->calendar_id : MicrosoftCalendarGateway::DEFAULT_CALENDAR,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
            'scopes' => $tokens['scope'],
            'status' => 'connected',
            'last_error' => null,
        ]);
        $this->calendar->forget($channelId);

        return $this->back('success', __('meetings.calendar_connected_now', ['email' => $email]));
    }

    public function select(Request $request)
    {
        $connection = $this->connection();
        $data = $request->validate(['calendar_id' => ['required', 'string', 'max:255']]);

        try {
            $calendars = collect($this->calendar->listCalendars($connection));
        } catch (Throwable $e) {
            return $this->back('error', __('meetings.calendar_list_failed'));
        }
        $chosen = $calendars->firstWhere('id', $data['calendar_id']);
        if (! $chosen) {
            return $this->back('error', __('meetings.calendar_unknown'));
        }

        $connection->update(['calendar_id' => $chosen['primary'] ? MicrosoftCalendarGateway::DEFAULT_CALENDAR : $chosen['id']]);
        $this->calendar->forget($this->channelId());

        return $this->back('success', __('meetings.calendar_selected'));
    }

    /** Forget the tokens. Microsoft has no per-app token revoke; the account owner can remove the app's access in their Microsoft account. */
    public function disconnect()
    {
        $this->connection()->delete();
        $this->calendar->forget($this->channelId());

        return $this->back('success', __('meetings.calendar_disconnected'));
    }

    private function hasCalendarScope(string $scopes): bool
    {
        return collect(preg_split('/\s+/', strtolower($scopes)))
            ->contains(fn ($scope) => str_ends_with($scope, 'calendars.readwrite'));
    }

    private function redirectUri(): string
    {
        return route('admin.meetings.calendar.callback');
    }

    private function connection(): MeetingCalendarConnection
    {
        return MeetingCalendarConnection::query()->where('channel_id', $this->channelId())->firstOrFail();
    }

    private function back(string $level, string $message)
    {
        return redirect()->route('admin.meetings.settings.edit')->with($level, $message);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
