<?php

namespace App\Services\Meetings\Calendar;

use App\Models\MeetingCalendarConnection;
use App\Services\Meetings\MeetingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Microsoft 365 calendar over Microsoft Graph, using Laravel's HTTP client:
 * OAuth (authorization code + refresh), busy times and event writes for a
 * channel's connected Outlook calendar, including Teams meeting links.
 */
class MicrosoftCalendarGateway implements CalendarGateway
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';

    private const LOGIN = 'https://login.microsoftonline.com';

    /** Delegated permissions: read/write the user's calendars, sign-in, and a refresh token. */
    public const SCOPES = 'offline_access User.Read Calendars.ReadWrite';

    /** The connected account's default calendar. */
    public const DEFAULT_CALENDAR = 'default';

    /** How long busy times are reused before Microsoft is asked again. */
    private const CACHE_SECONDS = 60;

    /** Free-busy states that leave the time bookable. */
    private const FREE = ['free', 'workingElsewhere'];

    public function __construct(private MeetingSettings $settings) {}

    public function connection(int $channelId): ?MeetingCalendarConnection
    {
        return MeetingCalendarConnection::query()->where('channel_id', $channelId)->first();
    }

    /* ──────────────── OAuth ───────────────────────────────────────── */

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        return $this->loginUrl('authorize').'?'.http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => self::SCOPES,
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    /**
     * Exchange an authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_in: int, scope: string}
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->timeout(10)->post($this->loginUrl('token'), [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => self::SCOPES,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Microsoft sign-in failed: '.($response->json('error_description') ?: $response->json('error') ?: 'HTTP '.$response->status()));
        }

        return [
            'access_token' => (string) $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
            'expires_in' => (int) $response->json('expires_in', 3600),
            'scope' => (string) $response->json('scope', ''),
        ];
    }

    /** The signed-in account's email address. */
    public function accountEmail(string $accessToken): string
    {
        $me = Http::withToken($accessToken)->acceptJson()->timeout(10)
            ->get(self::GRAPH.'/me', ['$select' => 'mail,userPrincipalName'])->throw();

        return (string) ($me->json('mail') ?: $me->json('userPrincipalName'));
    }

    /* ──────────────── Busy times ──────────────────────────────────── */

    public function busyIntervals(int $channelId, CarbonImmutable $from, CarbonImmutable $to, ?string $excludeEventId = null, bool $fresh = false): array
    {
        $connection = $this->connection($channelId);
        if (! $connection) {
            return [];
        }

        // Whole UTC days, so nearby requests share one cached lookup.
        $from = $from->utc()->startOfDay();
        $to = $to->utc()->startOfDay()->addDay();

        try {
            if (! $connection->isConnected()) {
                throw new RuntimeException($connection->last_error ?: 'The calendar connection is not usable.');
            }
            $load = fn () => $this->fetchBusy($connection, $from, $to);
            $events = $fresh ? $load() : Cache::remember($this->cacheKey($channelId, $from, $to), self::CACHE_SECONDS, $load);
        } catch (Throwable $e) {
            Log::warning('[Meetings] Microsoft 365 busy times unavailable: '.$e->getMessage());
            if ($this->settings->bool('general.fail_closed_when_calendar_unreachable', $channelId)) {
                throw new CalendarUnavailableException;
            }

            return [];
        }

        return collect($events)
            ->reject(fn (array $event) => $excludeEventId !== null && $event[0] === $excludeEventId)
            ->map(fn (array $event) => [CarbonImmutable::parse($event[1]), CarbonImmutable::parse($event[2])])
            ->values()
            ->all();
    }

    /** Drop cached busy times after the calendar changed. */
    public function forget(int $channelId): void
    {
        Cache::forever("meetings:busy-version:{$channelId}", (int) Cache::get("meetings:busy-version:{$channelId}", 0) + 1);
    }

    /* ──────────────── Events ──────────────────────────────────────── */

    public function createEvent(MeetingCalendarConnection $connection, array $body): array
    {
        return $this->request($connection, 'post', $this->calendarPath($connection).'/events', $body)->json();
    }

    public function patchEvent(MeetingCalendarConnection $connection, string $eventId, array $body): array
    {
        return $this->request($connection, 'patch', '/me/events/'.rawurlencode($eventId), $body)->json();
    }

    public function getEvent(MeetingCalendarConnection $connection, string $eventId): array
    {
        return $this->request($connection, 'get', '/me/events/'.rawurlencode($eventId).'?'.http_build_query(['$select' => 'id,isOnlineMeeting,onlineMeeting']))->json();
    }

    /** Delete an event; one that is already gone counts as deleted. */
    public function deleteEvent(MeetingCalendarConnection $connection, string $eventId): void
    {
        try {
            $this->request($connection, 'delete', '/me/events/'.rawurlencode($eventId));
        } catch (RequestException $e) {
            if (! in_array($e->response->status(), [404, 410], true)) {
                throw $e;
            }
        }
    }

    /** Calendars the account can add events to. @return list<array{id: string, name: string, primary: bool}> */
    public function listCalendars(MeetingCalendarConnection $connection): array
    {
        return collect($this->request($connection, 'get', '/me/calendars?'.http_build_query(['$select' => 'id,name,isDefaultCalendar,canEdit', '$top' => 100]))->json('value', []))
            ->filter(fn (array $calendar) => $calendar['canEdit'] ?? false)
            ->map(fn (array $calendar) => ['id' => $calendar['id'], 'name' => $calendar['name'] ?? $calendar['id'], 'primary' => (bool) ($calendar['isDefaultCalendar'] ?? false)])
            ->sortByDesc('primary')
            ->values()
            ->all();
    }

    /**
     * Blocking events as [id, start, end] (ISO strings, so the result can be
     * cached). All-day events cover their dates in the business timezone.
     */
    private function fetchBusy(MeetingCalendarConnection $connection, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $timezone = $this->settings->timezone((int) $connection->channel_id);
        $events = [];
        $url = $this->calendarPath($connection).'/calendarView?'.http_build_query([
            'startDateTime' => $from->toIso8601ZuluString(),
            'endDateTime' => $to->toIso8601ZuluString(),
            '$select' => 'id,showAs,isCancelled,isAllDay,start,end',
            '$top' => 500,
        ]);

        do {
            $page = $this->request($connection, 'get', $url)->json();

            foreach ($page['value'] ?? [] as $event) {
                if (($event['isCancelled'] ?? false) || in_array($event['showAs'] ?? 'busy', self::FREE, true)) {
                    continue;
                }
                [$start, $end] = ($event['isAllDay'] ?? false)
                    ? [CarbonImmutable::parse(substr($event['start']['dateTime'], 0, 10), $timezone), CarbonImmutable::parse(substr($event['end']['dateTime'], 0, 10), $timezone)]
                    : [CarbonImmutable::parse($event['start']['dateTime'], 'UTC'), CarbonImmutable::parse($event['end']['dateTime'], 'UTC')];
                $events[] = [$event['id'] ?? '', $start->utc()->toIso8601String(), $end->utc()->toIso8601String()];
            }
            $url = $page['@odata.nextLink'] ?? null;
        } while ($url);

        return $events;
    }

    /**
     * An authorized Graph call ($path is relative to v1.0 or a full nextLink).
     * Times come back in UTC; a 401 refreshes the token once and retries.
     */
    private function request(MeetingCalendarConnection $connection, string $method, string $path, ?array $body = null): Response
    {
        $url = str_starts_with($path, 'https://') ? $path : self::GRAPH.$path;
        $send = function (bool $forceRefresh) use ($connection, $method, $url, $body) {
            $client = Http::withToken($this->accessToken($connection, $forceRefresh))
                ->acceptJson()->withHeaders(['Prefer' => 'outlook.timezone="UTC"'])->timeout(10);

            return $body === null ? $client->send(strtoupper($method), $url) : $client->send(strtoupper($method), $url, ['json' => $body]);
        };

        $response = $send(false);
        if ($response->status() === 401) {
            $response = $send(true);
        }

        return $response->throw();
    }

    private function accessToken(MeetingCalendarConnection $connection, bool $force = false): string
    {
        if (! $force && $connection->access_token && $connection->token_expires_at?->isAfter(now()->addMinute())) {
            return $connection->access_token;
        }

        $response = Http::asForm()->timeout(10)->post($this->loginUrl('token'), [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
            'scope' => self::SCOPES,
        ]);

        if ($response->failed()) {
            $error = (string) ($response->json('error_description') ?: $response->json('error') ?: 'HTTP '.$response->status());
            // A revoked or expired grant needs the admin to connect again.
            $expired = in_array($response->json('error'), ['invalid_grant', 'interaction_required'], true);
            $connection->update(['status' => $expired ? 'expired' : $connection->status, 'last_error' => strtok($error, "\r\n")]);
            throw new RuntimeException('Microsoft token refresh failed: '.$error);
        }

        $connection->update([
            'access_token' => $response->json('access_token'),
            // Microsoft may rotate the refresh token.
            'refresh_token' => $response->json('refresh_token') ?: $connection->refresh_token,
            'token_expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600)),
            'status' => 'connected',
            'last_error' => null,
        ]);

        return $connection->access_token;
    }

    private function calendarPath(MeetingCalendarConnection $connection): string
    {
        return $connection->calendar_id === self::DEFAULT_CALENDAR || ! $connection->calendar_id
            ? '/me/calendar'
            : '/me/calendars/'.rawurlencode($connection->calendar_id);
    }

    private function loginUrl(string $endpoint): string
    {
        return self::LOGIN.'/'.rawurlencode((string) config('services.microsoft.tenant', 'organizations')).'/oauth2/v2.0/'.$endpoint;
    }

    private function cacheKey(int $channelId, CarbonImmutable $from, CarbonImmutable $to): string
    {
        $version = (int) Cache::get("meetings:busy-version:{$channelId}", 0);

        return "meetings:busy:{$channelId}:{$version}:{$from->getTimestamp()}:{$to->getTimestamp()}";
    }
}
