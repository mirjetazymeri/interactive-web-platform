<?php

namespace App\Http\Controllers\Admin\Meetings;

use App\Enums\MeetingFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Meetings\AvailabilityExceptionRequest;
use App\Http\Requests\Admin\Meetings\AvailabilityRulesRequest;
use App\Models\MeetingAvailabilityException;
use App\Models\MeetingAvailabilityRule;
use App\Models\MeetingType;
use App\Services\Meetings\AvailabilityService;
use App\Services\Meetings\Calendar\CalendarUnavailableException;
use App\Services\Meetings\MeetingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    /** Days shown in the slot preview. */
    private const PREVIEW_DAYS = 7;

    public function __construct(private AvailabilityService $availability, private MeetingSettings $settings) {}

    public function index(Request $request)
    {
        $channelId = $this->channelId();
        $timezone = $this->settings->timezone($channelId);
        $types = MeetingType::query()->forChannel($channelId)->orderBy('position')->orderBy('name')->get();

        $type = $types->firstWhere('id', (int) $request->query('type'));
        $format = MeetingFormat::tryFrom((string) $request->query('format'));
        if ($type && $format && ! $type->allows($format)) {
            $format = null;
        }

        $hours = MeetingAvailabilityRule::query()->where('channel_id', $channelId)
            ->where('meeting_type_id', $type?->id)->where('format', $format?->value)
            ->orderBy('start_time')->get()
            ->groupBy('weekday')
            ->map(fn ($rules) => $rules->map(fn ($rule) => ['start' => substr($rule->start_time, 0, 5), 'end' => substr($rule->end_time, 0, 5)])->values()->all());

        // Which hours each format of the selected type actually uses.
        $effective = $type ? collect($type->allowedFormats())->mapWithKeys(fn (MeetingFormat $f) => [
            $f->value => $this->availability->effectiveScope($type, $f),
        ]) : collect();

        $exceptions = MeetingAvailabilityException::query()->where('channel_id', $channelId)
            ->whereDate('date', '>=', CarbonImmutable::now($timezone)->toDateString())
            ->with('meetingType')
            ->orderBy('date')->orderBy('start_time')
            ->get();

        return view('admin.meetings.availability.index', [
            'types' => $types,
            'type' => $type,
            'format' => $format,
            'hours' => $hours,
            'effective' => $effective,
            'exceptions' => $exceptions,
            'timezone' => $timezone,
            'weekdays' => $this->weekdays(),
            'preview' => $this->preview($request, $types, $timezone),
        ]);
    }

    public function update(AvailabilityRulesRequest $request)
    {
        $scope = $request->scope();
        $channelId = $this->channelId();

        DB::transaction(function () use ($request, $scope, $channelId) {
            MeetingAvailabilityRule::query()->where('channel_id', $channelId)
                ->where('meeting_type_id', $scope['meeting_type_id'])->where('format', $scope['format'])
                ->delete();

            foreach ($request->ranges() as $range) {
                MeetingAvailabilityRule::create($scope + $range + ['channel_id' => $channelId]);
            }
        });

        return redirect()->route('admin.meetings.availability.index', $this->scopeQuery($scope))
            ->with('success', __('meetings.hours_saved'));
    }

    public function storeException(AvailabilityExceptionRequest $request)
    {
        MeetingAvailabilityException::create($request->payload() + ['channel_id' => $this->channelId()]);

        return back()->with('success', __('meetings.exception_saved'));
    }

    public function destroyException(int $exception)
    {
        MeetingAvailabilityException::query()->where('channel_id', $this->channelId())->findOrFail($exception)->delete();

        return back()->with('success', __('meetings.exception_deleted'));
    }

    /** Weekday numbers (0 = Sunday) in display order, Monday first, with localized names. */
    private function weekdays(): array
    {
        $monday = CarbonImmutable::now()->locale(app()->getLocale())->startOfWeek(CarbonImmutable::MONDAY);

        return collect(range(0, 6))->mapWithKeys(fn ($offset) => [
            $monday->addDays($offset)->dayOfWeek => $monday->addDays($offset)->dayName,
        ])->all();
    }

    /** Upcoming bookable slots for one type and format, grouped by local date. */
    private function preview(Request $request, $types, string $timezone): array
    {
        $type = $types->firstWhere('id', (int) $request->query('preview_type', $request->query('type')));
        if (! $type) {
            return ['type' => null, 'format' => null, 'days' => [], 'error' => null];
        }

        $format = MeetingFormat::tryFrom((string) $request->query('preview_format'));
        $format = $format && $type->allows($format) ? $format : $type->default_format;
        if (! $type->allows($format)) {
            $format = $type->allowedFormats()[0];
        }

        $from = CarbonImmutable::now($timezone)->startOfDay();
        try {
            $slots = $this->availability->slots($type, $format, $from, $from->addDays(self::PREVIEW_DAYS));
        } catch (CalendarUnavailableException $e) {
            return ['type' => $type, 'format' => $format, 'days' => [], 'error' => $e->getMessage()];
        }
        $days = collect($slots)
            ->groupBy(fn ($slot) => $slot['start']->setTimezone($timezone)->toDateString())
            ->map(fn ($slots) => $slots->map(fn ($slot) => $slot['start']->setTimezone($timezone)->format('H:i'))->all())
            ->all();

        return ['type' => $type, 'format' => $format, 'days' => $days, 'error' => null];
    }

    private function scopeQuery(array $scope): array
    {
        return array_filter(['type' => $scope['meeting_type_id'], 'format' => $scope['format']]);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
