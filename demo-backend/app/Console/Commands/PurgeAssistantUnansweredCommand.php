<?php

namespace App\Console\Commands;

use App\Models\AssistantProfile;
use App\Models\AssistantUnansweredQuestion;
use App\Models\Channel;
use Illuminate\Console\Command;

class PurgeAssistantUnansweredCommand extends Command
{
    protected $signature = 'assistant:purge-unanswered {--dry-run : Show counts without deleting}';

    protected $description = 'Delete unanswered assistant questions older than each channel\'s retention period';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach (Channel::query()->select(['id', 'code'])->get() as $channel) {
            $days = max(7, (int) (AssistantProfile::query()->forChannel($channel->id)->value('unanswered_retention_days') ?? 90));
            $query = AssistantUnansweredQuestion::query()->forChannel($channel->id)->where('last_seen_at', '<', now()->subDays($days));
            $count = (clone $query)->count();
            if (! $dryRun) {
                $query->delete();
            }
            $total += $count;
            $this->line("[{$channel->code}] retention={$days}d questions={$count}");
        }

        $this->info(($dryRun ? 'Dry run' : 'Deleted').": {$total} unanswered question(s).");

        return self::SUCCESS;
    }
}
