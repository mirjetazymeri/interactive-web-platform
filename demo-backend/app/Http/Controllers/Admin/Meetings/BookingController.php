<?php

namespace App\Http\Controllers\Admin\Meetings;

use App\Enums\MeetingFormat;
use App\Http\Controllers\Controller;
use App\Models\MeetingBooking;
use App\Models\MeetingType;
use App\Models\OfficeLocation;
use App\Services\Meetings\BookingException;
use App\Services\Meetings\BookingService;
use App\Services\Meetings\MeetingLocationPresenter;
use App\Services\Meetings\MeetingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public const STATUSES = ['confirmed', 'pending_approval', 'cancelled', 'completed', 'no_show'];

    public function __construct(private BookingService $bookings, private MeetingSettings $settings) {}

    public function index(Request $request)
    {
        $channelId = $this->channelId();
        $timezone = $this->settings->timezone($channelId);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'format' => ['nullable', Rule::in(MeetingFormat::values())],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'type' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'when' => ['nullable', Rule::in(['upcoming', 'past', 'all'])],
        ]);
        $when = $filters['when'] ?? 'upcoming';
        $local = fn (string $date) => CarbonImmutable::parse($date, $timezone);

        $bookings = MeetingBooking::query()->forChannel($channelId)
            ->with('meetingType')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")->orWhere('reference', $term)))
            ->when($filters['format'] ?? null, fn ($q, $format) => $q->where('format', $format))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('meeting_type_id', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('starts_at', '>=', $local($from)->utc()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('starts_at', '<', $local($to)->addDay()->utc()))
            ->when($when === 'upcoming', fn ($q) => $q->where('ends_at', '>=', now())->orderBy('starts_at'))
            ->when($when === 'past', fn ($q) => $q->where('ends_at', '<', now())->orderByDesc('starts_at'))
            ->when($when === 'all', fn ($q) => $q->orderByDesc('starts_at'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.meetings.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters + ['when' => $when],
            'types' => MeetingType::withTrashed()->forChannel($channelId)->orderBy('name')->get(['id', 'name']),
            'timezone' => $timezone,
        ]);
    }

    public function show(int $booking, MeetingLocationPresenter $presenter)
    {
        $booking = $this->find($booking)->load(['meetingType.translations', 'events.user']);
        $channelId = $this->channelId();

        return view('admin.meetings.bookings.show', [
            'booking' => $booking,
            'location' => $presenter->present($booking),
            'timezone' => $this->settings->timezone($channelId),
            'offices' => OfficeLocation::query()->forChannel($channelId)->where('active', true)->orderBy('position')->orderBy('name')->get(),
            'changeable' => in_array($booking->status, MeetingBooking::BLOCKING_STATUSES, true),
        ]);
    }

    public function confirm(int $booking)
    {
        return $this->attempt(fn () => $this->bookings->confirm($this->find($booking), auth()->user()), 'booking_confirmed');
    }

    public function changeFormat(Request $request, int $booking)
    {
        $model = $this->find($booking);
        $data = $request->validate([
            'format' => ['required', Rule::in(MeetingFormat::values())],
            'office_location_id' => ['nullable', 'integer'],
            'start' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'notify' => ['boolean'],
        ]);

        return $this->attempt(fn () => $this->bookings->changeFormat(
            $model,
            MeetingFormat::from($data['format']),
            isset($data['start']) ? $this->localStart($data['start']) : null,
            isset($data['office_location_id']) ? (int) $data['office_location_id'] : null,
            $request->boolean('notify'),
            auth()->user(),
        ), 'booking_format_changed');
    }

    public function reschedule(Request $request, int $booking)
    {
        $model = $this->find($booking);
        $data = $request->validate(['start' => ['required', 'date_format:Y-m-d\TH:i'], 'notify' => ['boolean']]);

        return $this->attempt(fn () => $this->bookings->reschedule($model, $this->localStart($data['start']), $request->boolean('notify'), auth()->user()), 'booking_rescheduled');
    }

    public function cancel(Request $request, int $booking)
    {
        $model = $this->find($booking);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500'], 'notify' => ['boolean']]);

        return $this->attempt(fn () => $this->bookings->cancel($model, $data['reason'] ?? null, $request->boolean('notify'), auth()->user()), 'booking_cancelled');
    }

    public function setVideoLink(Request $request, int $booking)
    {
        $model = $this->find($booking);
        $data = $request->validate(['video_url' => ['required', 'url:https', 'max:2048']]);

        return $this->attempt(fn () => $this->bookings->setVideoLink($model, $data['video_url'], auth()->user()), 'booking_link_set');
    }

    public function retrySync(int $booking)
    {
        return $this->attempt(fn () => $this->bookings->retryCalendarSync($this->find($booking), auth()->user()), 'booking_sync_retried');
    }

    public function resend(int $booking)
    {
        return $this->attempt(fn () => $this->bookings->resendConfirmation($this->find($booking), auth()->user()), 'booking_resent');
    }

    /** Run a booking action, turning a refused change into a form error. */
    private function attempt(callable $action, string $message)
    {
        try {
            $action();
        } catch (BookingException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('success', __('meetings.'.$message));
    }

    /** A datetime-local value, entered in the business timezone. */
    private function localStart(string $value): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $value, $this->settings->timezone($this->channelId()));
    }

    private function find(int $id): MeetingBooking
    {
        return MeetingBooking::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
