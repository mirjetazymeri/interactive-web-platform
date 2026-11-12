<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

class GenerateTwoFactorKeyCommand extends Command
{
    protected $signature = 'key:generate-2fa
        {--show : Display the generated key in console output}
        {--force : Overwrite APP_KEY_2FA if it already exists}
        {--rotate : Rotate APP_KEY_2FA and push old value into APP_PREVIOUS_KEYS_2FA}
    ';

    protected $description = 'Generate and write APP_KEY_2FA to the .env file.';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('.env file not found at project root.');

            return self::FAILURE;
        }

        $contents = file_get_contents($envPath);

        if ($contents === false) {
            $this->error('Unable to read .env file.');

            return self::FAILURE;
        }

        $newKey = $this->generateKey();

        $currentKey = $this->readEnvValue($contents, 'APP_KEY_2FA');
        $hasCurrentKey = $currentKey !== null && $currentKey !== '';

        $force = (bool) $this->option('force');
        $rotate = (bool) $this->option('rotate');

        if ($hasCurrentKey && ! $force && ! $rotate) {
            $this->warn('APP_KEY_2FA already exists.');
            $this->line('Use --rotate to keep backward compatibility or --force to overwrite directly.');

            return self::FAILURE;
        }

        $contents = $this->writeEnvValue($contents, 'APP_KEY_2FA', $newKey);

        if ($rotate && $hasCurrentKey) {
            $contents = $this->appendPreviousTwoFactorKey($contents, $currentKey);
            $this->info('Rotated APP_KEY_2FA and appended previous key to APP_PREVIOUS_KEYS_2FA.');
        } elseif ($hasCurrentKey) {
            $this->warn('APP_KEY_2FA overwritten with --force.');
        } else {
            $this->info('APP_KEY_2FA generated and written to .env.');
        }

        if (file_put_contents($envPath, $contents) === false) {
            $this->error('Failed to write updated .env file.');

            return self::FAILURE;
        }

        $this->line('Run: php artisan config:clear');

        if ($this->option('show')) {
            $this->newLine();
            $this->line('APP_KEY_2FA='.$newKey);
        }

        return self::SUCCESS;
    }

    private function generateKey(): string
    {
        $cipher = config('app.cipher', 'AES-256-CBC');
        $rawKey = Encrypter::generateKey($cipher);

        return 'base64:'.base64_encode($rawKey);
    }

    private function readEnvValue(string $contents, string $name): ?string
    {
        $pattern = '/^'.preg_quote($name, '/').'=(.*)$/m';

        if (! preg_match($pattern, $contents, $matches)) {
            return null;
        }

        return trim((string) $matches[1]);
    }

    private function writeEnvValue(string $contents, string $name, string $value): string
    {
        $pattern = '/^'.preg_quote($name, '/').'=.*$/m';
        $line = $name.'='.$value;

        if (preg_match($pattern, $contents)) {
            return (string) preg_replace($pattern, $line, $contents, 1);
        }

        $suffix = str_ends_with($contents, PHP_EOL) ? '' : PHP_EOL;

        return $contents.$suffix.$line.PHP_EOL;
    }

    private function appendPreviousTwoFactorKey(string $contents, string $oldKey): string
    {
        $current = $this->readEnvValue($contents, 'APP_PREVIOUS_KEYS_2FA') ?? '';

        $keys = array_values(array_filter(array_map('trim', explode(',', $current))));

        if (! in_array($oldKey, $keys, true)) {
            $keys[] = $oldKey;
        }

        return $this->writeEnvValue($contents, 'APP_PREVIOUS_KEYS_2FA', implode(',', $keys));
    }
}
