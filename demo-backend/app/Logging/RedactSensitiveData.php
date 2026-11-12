<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\LogRecord;

/**
 * Log channel tap (config/logging.php → 'tap'): secrets are masked in the
 * message and context of every record, and in the formatted output.
 */
class RedactSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();
        $redactor = SecretRedactor::fromConfig();

        $monolog->pushProcessor(fn (LogRecord $record) => $record->with(
            message: $redactor->text($record->message),
            context: $redactor->array($record->context),
            extra: $redactor->array($record->extra),
        ));

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface && ! $handler->getFormatter() instanceof RedactingFormatter) {
                $handler->setFormatter(new RedactingFormatter($handler->getFormatter(), $redactor));
            }
        }
    }
}
