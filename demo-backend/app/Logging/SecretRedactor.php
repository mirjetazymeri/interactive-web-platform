<?php

namespace App\Logging;

/**
 * Masks secrets in text that leaves the application (logs, error responses):
 * the values of secret configuration entries, values stored under secret-like
 * keys, Bearer/Basic credentials, credentials in URLs and "password=…",
 * 'username "…"' style fragments (as in SMTP authentication errors).
 */
class SecretRedactor
{
    public const MASK = '[REDACTED]';

    /** Keys whose values are secret wherever they appear (config, log context, payloads). */
    public const SECRET_KEY = '/(^|[_.\-])(pass(word|wd|phrase)?|secret|token|api[_\-]?key|authori[sz]ation|cookie|credentials?|private[_\-]?key|client[_\-]?secret|two_factor_key|previous_keys)$|^(username|mail_username|smtp_username)$/i';

    /** In configuration a bare "key" is secret too (app.key, a service access key); in log context it usually is not. */
    private const CONFIG_KEY = '/^key$/i';

    /** @param list<string> $values Exact secret values to mask, longest first. */
    public function __construct(private readonly array $values = []) {}

    /** The secret values currently configured (mail, database, app keys, third-party services, assistant). */
    public static function fromConfig(): self
    {
        $values = [];
        $secret = fn (string $key) => preg_match(self::SECRET_KEY, $key) || preg_match(self::CONFIG_KEY, $key);
        $collect = function (mixed $node, string $key = '') use (&$collect, &$values, $secret) {
            if (is_array($node)) {
                foreach ($node as $childKey => $child) {
                    // Arrays under a secret key (e.g. previous_keys) are secret as a whole.
                    $collect($child, $secret($key) ? $key : (string) $childKey);
                }

                return;
            }
            if (is_string($node) && strlen($node) >= 6 && $secret($key)) {
                $values[] = $node;
            }
        };
        foreach (['app.key', 'app.previous_keys', 'mail.mailers', 'database.connections', 'database.redis', 'services', 'fortify.two_factor_key', 'fortify.two_factor_previous_keys', 'assistant.preview_token'] as $path) {
            $value = config($path);
            $collect($value, str_contains($path, '.') ? substr($path, strrpos($path, '.') + 1) : $path);
        }
        $values = array_values(array_unique($values));
        usort($values, fn ($a, $b) => strlen($b) <=> strlen($a));

        return new self($values);
    }

    public function text(string $text): string
    {
        if ($text === '') {
            return $text;
        }
        if ($this->values !== []) {
            $text = str_replace($this->values, self::MASK, $text);
        }

        return preg_replace([
            '/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=\-]{6,}/i',
            '#\b([a-z][a-z0-9+.\-]*://)[^\s/:@]+:[^\s/@]+@#i',
            '/\b(password|passwd|pwd|secret|client_secret|access_token|refresh_token|api[_\-]?key|token)(\s*["\']?\s*[:=]\s*["\']?)[^"\'\s,&;}]+/i',
            '/\b(username|password)(\s+")[^"]+"/i',
        ], [
            '$1 '.self::MASK,
            '$1'.self::MASK.'@',
            '$1$2'.self::MASK,
            '$1$2'.self::MASK.'"',
        ], $text) ?? $text;
    }

    /** Redacts strings and secret-keyed values in nested arrays; objects (e.g. exceptions) are left to the formatter. */
    public function array(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SECRET_KEY, $key) && (is_scalar($value) || is_array($value)) && $value !== '' && $value !== null) {
                $data[$key] = self::MASK;
            } elseif (is_array($value)) {
                $data[$key] = $this->array($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->text($value);
            }
        }

        return $data;
    }
}
