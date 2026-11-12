<?php

namespace App\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * Wraps a channel's formatter and masks secrets in its final output, so the
 * rendered exception messages and stack traces are covered too.
 */
class RedactingFormatter implements FormatterInterface
{
    public function __construct(private readonly FormatterInterface $inner, private readonly SecretRedactor $redactor) {}

    public function format(LogRecord $record)
    {
        return $this->redact($this->inner->format($record));
    }

    public function formatBatch(array $records)
    {
        return $this->redact($this->inner->formatBatch($records));
    }

    private function redact(mixed $output): mixed
    {
        return match (true) {
            is_string($output) => $this->redactor->text($output),
            is_array($output) => $this->redactor->array($output),
            default => $output,
        };
    }
}
