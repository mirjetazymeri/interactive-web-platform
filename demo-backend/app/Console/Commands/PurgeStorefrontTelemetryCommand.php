<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\StorefrontConsent;
use App\Models\StorefrontEvent;
use App\Services\SettingService;
use Illuminate\Console\Command;

class PurgeStorefrontTelemetryCommand extends Command
{
    protected $signature = 'gdpr:purge-telemetry {--days=} {--dry-run : Show counts without deleting}';

    protected $description = 'Purge old storefront telemetry records according to GDPR retention settings';

    public function handle(SettingService $settings): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $forcedDays = $this->option('days');

        $totalEvents = 0;
        $totalConsents = 0;

        foreach (Channel::query()->select(['id', 'code'])->get() as $channel) {
            $days = is_numeric($forcedDays)
                ? max(1, (int) $forcedDays)
                : max(1, (int) $settings->get('gdpr.analytics_retention_days', 180, (int) $channel->id));

            $cutoff = now()->subDays($days);

            $eventsQuery = StorefrontEvent::query()
                ->where('channel_id', (int) $channel->id)
                ->where('occurred_at', '<', $cutoff);

            $consentsQuery = StorefrontConsent::query()
                ->where('channel_id', (int) $channel->id)
                ->whereNotNull('withdrawn_at')
                ->where('withdrawn_at', '<', $cutoff);

            $eventCount = (clone $eventsQuery)->count();
            $consentCount = (clone $consentsQuery)->count();

            if (!$dryRun) {
                $eventsQuery->delete();
                $consentsQuery->delete();
            }

            $totalEvents += $eventCount;
            $totalConsents += $consentCount;

            $this->line("[{$channel->code}] retention={$days}d events={$eventCount} consents={$consentCount}");
        }

        $mode = $dryRun ? 'Dry run' : 'Deleted';
        $this->info("{$mode}: {$totalEvents} event(s), {$totalConsents} consent record(s).");

        return self::SUCCESS;
    }
}
