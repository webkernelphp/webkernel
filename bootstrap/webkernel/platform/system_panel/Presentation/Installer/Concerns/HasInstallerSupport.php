<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns;

use Illuminate\Support\Str;

/**
 * Private support helpers for the InstallerPage.
 *
 * - Phase resolution after install
 * - Setup token generation + persistence
 * - Requirements list
 */
trait HasInstallerSupport
{
    /**
     * Decide which phase comes right after a successful install (or resume).
     *
     * - WEBKERNEL_SETUP_TOKEN explicitly set in env → require token validation.
     * - Not set → skip the token step and go straight to claim_choice.
     */
    private function resolvePostInstallPhase(): string
    {
        $envToken = config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN');

        return empty($envToken) ? 'setup' : 'verify_token';
    }

    /**
     * Return the authoritative setup token.
     *
     * Priority:
     *   1. WEBKERNEL_SETUP_TOKEN from env / config  (operator-controlled)
     *   2. Auto-generated token at storage/webkernel/.setup_token
     *      (created on first load; burned after successful validation)
     */
    private function resolveSetupToken(): string
    {
        $envToken = config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN');

        if (! empty($envToken)) {
            return (string) $envToken;
        }

        $tokenFile = storage_path('webkernel/.setup_token');

        if (! file_exists($tokenFile)) {
            $dir = dirname($tokenFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0700, true);
            }
            file_put_contents($tokenFile, Str::random(48), LOCK_EX);
            chmod($tokenFile, 0600);
        }

        return trim((string) file_get_contents($tokenFile));
    }

    /**
     * @return list<array{id: string, label: string, ok: bool, value: string}>
     */
    private function buildRequirements(): array
    {
        return [
            ['id' => 'php',      'label' => 'PHP >= 8.4',                'ok' => version_compare(PHP_VERSION, '8.4.0', '>='), 'value' => PHP_VERSION],
            ['id' => 'openssl',  'label' => 'OpenSSL extension',         'ok' => extension_loaded('openssl'),                 'value' => ''],
            ['id' => 'pdo',      'label' => 'PDO extension',             'ok' => extension_loaded('pdo'),                     'value' => ''],
            ['id' => 'mbstring', 'label' => 'Mbstring extension',        'ok' => extension_loaded('mbstring'),                'value' => ''],
            ['id' => 'xml',      'label' => 'XML / DOM extension',       'ok' => extension_loaded('xml'),                     'value' => ''],
            ['id' => 'json',     'label' => 'JSON extension',            'ok' => extension_loaded('json'),                    'value' => ''],
            ['id' => 'ctype',    'label' => 'Ctype extension',           'ok' => extension_loaded('ctype'),                   'value' => ''],
            ['id' => 'bcmath',   'label' => 'BCMath extension',          'ok' => extension_loaded('bcmath'),                  'value' => ''],
            ['id' => 'storage',  'label' => 'storage/ writable',         'ok' => is_writable(storage_path()),                 'value' => ''],
            ['id' => 'cache',    'label' => 'bootstrap/cache/ writable', 'ok' => is_writable(base_path('bootstrap/cache')),   'value' => ''],
        ];
    }
}
