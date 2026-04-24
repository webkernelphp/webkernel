<?php declare(strict_types=1);

namespace Webkernel\Connectors;

// bootstrap/webkernel/aptitudes/connectors/facades/Mailer.php
// Autoloaded as:
//   'Webkernel\\Connectors\\' => WEBKERNEL_PATH . '/aptitudes/connectors/facades'

use Illuminate\Support\Facades\Mail;

/**
 * Webkernel Mailer facade.
 *
 * Thin wrapper around Laravel's Mail system that adds:
 *   - runtime SMTP reconfiguration (used by the installer)
 *   - a single isConfigured() check so callers can decide
 *     whether to send or fall back to on-screen links
 *
 * All heavy logic (templates, queued mail jobs, etc.) lives in
 * domain classes under Webkernel\Communication\*. This class is
 * intentionally minimal — it is a Connector facade, not a mailer service.
 */
class Mailer
{
    // ── Status ────────────────────────────────────────────────────────────────

    /**
     * Returns true when the current mail driver is a real SMTP transport.
     * Returns false for log / array / null drivers (testing / unconfigured).
     */
    public static function isConfigured(): bool
    {
        $driver = config('mail.default', 'log');
        return ! in_array($driver, ['log', 'array', 'null'], true);
    }

    // ── Send ──────────────────────────────────────────────────────────────────

    /**
     * Send a plain HTML email.
     *
     * Returns true on success, false on any transport failure.
     * Swallows exceptions so callers can silently fall back to on-screen links.
     */
    public static function sendHtml(string $to, string $subject, string $html): bool
    {
        try {
            Mail::html($html, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send a plain-text email.
     */
    public static function sendText(string $to, string $subject, string $text): bool
    {
        try {
            Mail::raw($text, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Configuration ─────────────────────────────────────────────────────────

    /**
     * Configure SMTP at runtime and persist the settings to .env.
     *
     * Called by the installer's instance_config phase. After this call,
     * isConfigured() returns true and subsequent send*() calls use the
     * new transport without a server restart.
     *
     * @param array{
     *     host: string,
     *     port: string|int,
     *     username: string,
     *     password: string,
     *     encryption: 'tls'|'ssl'|'none',
     *     from_name: string,
     *     from_email: string,
     * } $smtp
     */
    public static function configure(array $smtp): void
    {
        $encryption = $smtp['encryption'] === 'none' ? null : $smtp['encryption'];

        // 1. Reconfigure in-memory so the current request can send mail immediately.
        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $smtp['host'],
            'mail.mailers.smtp.port'       => (int) $smtp['port'],
            'mail.mailers.smtp.username'   => $smtp['username'],
            'mail.mailers.smtp.password'   => $smtp['password'],
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address'            => $smtp['from_email'],
            'mail.from.name'               => $smtp['from_name'],
        ]);

        // Purge the cached mailer instance so it rebuilds with the new config.
        app('mail.manager')->purge('smtp');

        // 2. Persist to .env so settings survive a server restart.
        static::writeEnvValues([
            'MAIL_MAILER'       => 'smtp',
            'MAIL_HOST'         => $smtp['host'],
            'MAIL_PORT'         => (string) $smtp['port'],
            'MAIL_USERNAME'     => $smtp['username'],
            'MAIL_PASSWORD'     => static::quoteEnvValue($smtp['password']),
            'MAIL_ENCRYPTION'   => $smtp['encryption'],
            'MAIL_FROM_ADDRESS' => static::quoteEnvValue($smtp['from_email']),
            'MAIL_FROM_NAME'    => static::quoteEnvValue($smtp['from_name']),
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Write or update key=value pairs in the application .env file.
     * Existing keys are updated in-place; missing keys are appended.
     *
     * @param array<string, string> $values
     */
    private static function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $env = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $pattern = '/^(' . preg_quote($key, '/') . ')=.*/m';

            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, $key . '=' . $value, $env);
            } else {
                $env = rtrim($env) . "\n" . $key . '=' . $value . "\n";
            }
        }

        file_put_contents($envPath, $env, LOCK_EX);
    }

    /**
     * Quote a value that contains spaces, #, quotes, or backslashes.
     */
    private static function quoteEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"' . str_replace(['"', '\\'], ['\\"', '\\\\'], $value) . '"';
        }
        return $value;
    }
}
