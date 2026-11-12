<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantUnansweredQuestion;
use App\Models\MeetingBooking;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\User;
use App\Models\Widget;
use App\Services\Meetings\MeetingSettings;

class DashboardController extends Controller
{
    public function index(MeetingSettings $meetingSettings)
    {
        $user = auth()->user();
        $channelId = (int) $user->current_channel_id;

        $stats = [
            'pages' => Page::count(),
            'pages_published' => Page::where('status', 'published')->count(),
            'widgets' => Widget::query()->manageableBy($user)->count(),
            'subscribers' => NewsletterSubscriber::query()->forChannel($channelId)->where('status', 'subscribed')->count(),
            'subscribers_recent' => NewsletterSubscriber::query()->forChannel($channelId)->where('status', 'subscribed')
                ->where('created_at', '>=', now()->subDays(30))->count(),
            'bookings_upcoming' => MeetingBooking::query()->forChannel($channelId)->upcomingBlocking()->count(),
            'bookings_pending' => MeetingBooking::query()->forChannel($channelId)->where('status', 'pending_approval')
                ->where('ends_at', '>', now())->count(),
            'unanswered' => AssistantUnansweredQuestion::query()->forChannel($channelId)->where('status', 'new')->count(),
            'users' => User::count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'upcomingBookings' => MeetingBooking::query()->forChannel($channelId)->upcomingBlocking()
                ->with('meetingType')->orderBy('starts_at')->limit(5)->get(),
            'recentPages' => Page::query()->latest('updated_at')->limit(5)->get(),
            'recentSubscribers' => NewsletterSubscriber::query()->forChannel($channelId)->latest()->limit(5)->get(),
            'timezone' => $meetingSettings->timezone($channelId),
        ]);
    }
}
