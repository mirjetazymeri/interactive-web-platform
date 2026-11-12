<?php

namespace App\Services\Assistant;

use App\Models\AssistantUnansweredQuestion;
use Illuminate\Support\Str;

/**
 * Records questions the assistant could not answer, for admins to review.
 * Privacy: e-mail addresses, links and long numbers are replaced before
 * anything is stored, identical questions are aggregated into one row with
 * a count, and no IP address, session or visitor identifier is kept.
 */
class UnansweredQuestionLogger
{
    public function __construct(private AssistantNormalizer $normalizer) {}

    public function record(int $channelId, string $locale, string $message): void
    {
        $question = self::scrub($message);
        $folded = $this->normalizer->fold($question);
        if ($folded === '') {
            return;
        }
        $hash = hash('sha256', $folded);
        $now = now();

        try {
            $row = AssistantUnansweredQuestion::query()->forChannel($channelId)->where('locale', $locale)->where('hash', $hash)->first();
            if ($row) {
                $row->occurrences++;
                $row->last_seen_at = $now;
                // A question answered earlier that fails again needs another look.
                if ($row->status === 'resolved') {
                    $row->status = 'new';
                }
                $row->save();

                return;
            }
            AssistantUnansweredQuestion::create([
                'channel_id' => $channelId, 'locale' => $locale, 'hash' => $hash, 'question' => $question,
                'occurrences' => 1, 'first_seen_at' => $now, 'last_seen_at' => $now, 'status' => 'new',
            ]);
        } catch (\Throwable $e) {
            // Analytics must never break the visitor's answer.
            report($e);
        }
    }

    /** The question without personal details, single-line and at most 200 characters. */
    public static function scrub(string $message): string
    {
        $text = preg_replace('/[^\P{C}]+/u', ' ', $message) ?? '';
        $text = preg_replace('/\b[\w.+-]+@[\w-]+(\.[\w-]+)+\b/u', '[email]', $text) ?? '';
        $text = preg_replace('#\b(?:https?://|www\.)\S+#iu', '[link]', $text) ?? '';
        $text = preg_replace('/\+?\d[\d\s().\/-]{4,}\d/u', '[number]', $text) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return Str::limit($text, 197, '…');
    }
}
