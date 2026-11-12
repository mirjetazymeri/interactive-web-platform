@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
@php
    $firstName = \Illuminate\Support\Str::of(Auth::user()->name)->explode(' ')->first();
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $statusBadge = [
        'published' => 'bg-success', 'draft' => 'bg-secondary', 'scheduled' => 'bg-info', 'archived' => 'bg-secondary',
        'subscribed' => 'bg-success', 'unsubscribed' => 'bg-secondary', 'pending' => 'bg-warning',
        'confirmed' => 'bg-success', 'pending_approval' => 'bg-warning',
    ];
@endphp

<div class="ax-page-header">
    <div>
        <h1 class="ax-page-header__title">{{ $greeting }}, {{ $firstName }}</h1>
        <p class="ax-page-header__desc">Overview of {{ $currentChannel->name ?? 'your channel' }} — {{ now()->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY') }}</p>
    </div>
    <div class="ax-page-header__actions">
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="ri-add-line me-1" aria-hidden="true"></i>New page</a>
    </div>
</div>

<div class="ax-stats">
    <a href="{{ route('admin.pages.index') }}" class="ax-stat">
        <div class="ax-stat__top"><span class="ax-stat__label">Pages</span><span class="ax-stat__icon" aria-hidden="true"><i class="ri-pages-line"></i></span></div>
        <div class="ax-stat__value">{{ number_format($stats['pages']) }}</div>
        <div class="ax-stat__meta">{{ number_format($stats['pages_published']) }} published</div>
    </a>
    <a href="{{ route('admin.widgets.index') }}" class="ax-stat">
        <div class="ax-stat__top"><span class="ax-stat__label">Widgets</span><span class="ax-stat__icon" aria-hidden="true"><i class="ri-layout-grid-line"></i></span></div>
        <div class="ax-stat__value">{{ number_format($stats['widgets']) }}</div>
        <div class="ax-stat__meta">In the page widget library</div>
    </a>
    <a href="{{ route('admin.meetings.bookings.index') }}" class="ax-stat">
        <div class="ax-stat__top"><span class="ax-stat__label">Upcoming meetings</span><span class="ax-stat__icon" aria-hidden="true"><i class="ri-calendar-event-line"></i></span></div>
        <div class="ax-stat__value">{{ number_format($stats['bookings_upcoming']) }}</div>
        <div class="ax-stat__meta">
            @if ($stats['bookings_pending'] > 0)
                <span class="badge bg-warning">{{ $stats['bookings_pending'] }} awaiting approval</span>
            @else
                None awaiting approval
            @endif
        </div>
    </a>
    <a href="{{ route('admin.newsletters.index') }}" class="ax-stat">
        <div class="ax-stat__top"><span class="ax-stat__label">Newsletter subscribers</span><span class="ax-stat__icon" aria-hidden="true"><i class="ri-mail-send-line"></i></span></div>
        <div class="ax-stat__value">{{ number_format($stats['subscribers']) }}</div>
        <div class="ax-stat__meta">+{{ number_format($stats['subscribers_recent']) }} in the last 30 days</div>
    </a>
    <a href="{{ route('admin.assistant.unanswered.index') }}" class="ax-stat">
        <div class="ax-stat__top"><span class="ax-stat__label">Unanswered questions</span><span class="ax-stat__icon" aria-hidden="true"><i class="ri-question-answer-line"></i></span></div>
        <div class="ax-stat__value">{{ number_format($stats['unanswered']) }}</div>
        <div class="ax-stat__meta">New questions the assistant could not answer</div>
    </a>
    <a href="{{ route('admin.users.index') }}" class="ax-stat">
        <div class="ax-stat__top"><span class="ax-stat__label">Users</span><span class="ax-stat__icon" aria-hidden="true"><i class="ri-user-line"></i></span></div>
        <div class="ax-stat__value">{{ number_format($stats['users']) }}</div>
        <div class="ax-stat__meta">Registered accounts</div>
    </a>
</div>

<div class="ax-grid">
    <div>
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Upcoming meetings</div>
                <a href="{{ route('admin.meetings.bookings.index') }}" class="btn btn-ghost btn-sm">View all</a>
            </div>
            <div class="card-body p-0">
                @if ($upcomingBookings->isEmpty())
                    <div class="ax-empty">
                        <i class="ri-calendar-line" aria-hidden="true"></i>
                        <div class="ax-empty__title">No upcoming meetings</div>
                        <div>New bookings appear here as soon as visitors schedule them.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Visitor</th>
                                    <th scope="col">Meeting</th>
                                    <th scope="col">When <span class="text-lowercase fw-normal">({{ $timezone }})</span></th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($upcomingBookings as $booking)
                                    @php $start = $booking->starts_at->copy()->setTimezone($timezone)->locale(app()->getLocale()); @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.meetings.bookings.show', $booking) }}" class="fw-medium text-reset text-decoration-none">{{ $booking->name }}</a>
                                            <div class="text-muted small">{{ $booking->email }}</div>
                                        </td>
                                        <td>{{ $booking->meetingType?->name ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $start->isoFormat('ddd, D MMM · HH:mm') }}</td>
                                        <td><span class="badge {{ $statusBadge[$booking->status] ?? 'bg-secondary' }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Recently updated pages</div>
                <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost btn-sm">All pages</a>
            </div>
            <div class="card-body p-0">
                @if ($recentPages->isEmpty())
                    <div class="ax-empty">
                        <i class="ri-pages-line" aria-hidden="true"></i>
                        <div class="ax-empty__title">No pages yet</div>
                        <a href="{{ route('admin.pages.create') }}">Create the first page</a>
                    </div>
                @else
                    <ul class="ax-list">
                        @foreach ($recentPages as $page)
                            <li class="ax-list__item">
                                <div class="ax-list__main">
                                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="ax-list__title d-block">{{ $page->title ?: '—' }}</a>
                                    <div class="ax-list__meta">/{{ $page->path ?? $page->slug }} · updated {{ $page->updated_at?->diffForHumans() ?? '—' }}</div>
                                </div>
                                <span class="badge {{ $statusBadge[$page->status] ?? 'bg-secondary' }}">{{ ucfirst((string) $page->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Quick actions</div></div>
            <div class="ax-actions">
                <a href="{{ route('admin.pages.create') }}" class="ax-action"><i class="ri-file-add-line" aria-hidden="true"></i><span>New page</span></a>
                <a href="{{ route('admin.widgets.index') }}" class="ax-action"><i class="ri-layout-grid-line" aria-hidden="true"></i><span>Widget library</span></a>
                <a href="{{ route('admin.meetings.availability.index') }}" class="ax-action"><i class="ri-time-line" aria-hidden="true"></i><span>Availability</span></a>
                <a href="{{ route('admin.assistant.questions.index') }}" class="ax-action"><i class="ri-robot-2-line" aria-hidden="true"></i><span>Assistant answers</span></a>
                <a href="{{ route('admin.seo.settings.edit') }}" class="ax-action"><i class="ri-search-eye-line" aria-hidden="true"></i><span>SEO settings</span></a>
                <a href="{{ route('admin.settings.index') }}" class="ax-action"><i class="ri-settings-3-line" aria-hidden="true"></i><span>All configs</span></a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Latest subscribers</div>
                <a href="{{ route('admin.newsletters.index') }}" class="btn btn-ghost btn-sm">View all</a>
            </div>
            <div class="card-body p-0">
                @if ($recentSubscribers->isEmpty())
                    <div class="ax-empty">
                        <i class="ri-mail-line" aria-hidden="true"></i>
                        <div class="ax-empty__title">No subscribers yet</div>
                    </div>
                @else
                    <ul class="ax-list">
                        @foreach ($recentSubscribers as $subscriber)
                            <li class="ax-list__item">
                                <span class="ax-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($subscriber->name ?: $subscriber->email, 0, 1)) }}</span>
                                <div class="ax-list__main">
                                    <div class="ax-list__title">{{ $subscriber->name ?: $subscriber->email }}</div>
                                    <div class="ax-list__meta">@if ($subscriber->name){{ $subscriber->email }} · @endif{{ $subscriber->created_at?->diffForHumans() }}</div>
                                </div>
                                <span class="badge {{ $statusBadge[$subscriber->status] ?? 'bg-secondary' }}">{{ ucfirst($subscriber->status) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
