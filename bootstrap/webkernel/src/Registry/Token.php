<?php declare(strict_types=1);

namespace Webkernel\Registry;

use Webkernel\CryptData;

/**
 * Centralised, encrypted token store for all remote registries.
 *
 * Replaces the previous Modules\Core\ConfigManager which was GitHub-specific.
 * Tokens are stored in a single JSON file under WEBKERNEL_CACHE_PATH,
 * encrypted with Laravel's Crypt facade (AES-256-CBC via CryptData).
 *
 * Token scopes:
 *   global       — applies to all repos under an owner/registry
 *   repo         — applies to a specific vendor/slug pair only
 *   registry     — applies to an entire registry (e.g. all of gitlab.com)
 *
 * Resolution order (most specific wins):
 *   1. repo scope   (registry + vendor + slug)
 *   2. owner scope  (registry + vendor)
 *   3. registry scope (registry only)
 */
final class Token
{
    private const FILE_NAME = 'registry-tokens.json';

    private string $path;
    private array  $data = [];

    public function __construct(?string $storageDir = null)
    {
        $dir        = $storageDir ?? (defined('WEBKERNEL_CACHE_PATH') ? WEBKERNEL_CACHE_PATH : sys_get_temp_dir());
        $this->path = rtrim($dir, '/') . '/' . self::FILE_NAME;
        $this->load();
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Resolve the best available token for a given Source.
     * Returns null when no token is stored for that source.
     */
    public function resolve(Source $source): ?string
    {
        return $this->forRepo($source->registry, $source->vendor, $source->slug)
            ?? $this->forOwner($source->registry, $source->vendor)
            ?? $this->forRegistry($source->registry);
    }

    public function forRepo(string $registry, string $vendor, string $slug): ?string
    {
        $raw = $this->data['tokens'][$registry][$vendor]['repos'][$slug] ?? null;
        return $raw !== null ? $this->decrypt($raw) : null;
    }

    public function forOwner(string $registry, string $vendor): ?string
    {
        $raw = $this->data['tokens'][$registry][$vendor]['global'] ?? null;
        return $raw !== null ? $this->decrypt($raw) : null;
    }

    public function forRegistry(string $registry): ?string
    {
        $raw = $this->data['tokens'][$registry]['_registry_global'] ?? null;
        return $raw !== null ? $this->decrypt($raw) : null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Persist a token for a specific repo.
     */
    public function saveForRepo(string $registry, string $vendor, string $slug, string $token): void
    {
        $this->ensureNode($registry, $vendor);
        $this->data['tokens'][$registry][$vendor]['repos'][$slug] = CryptData::encrypt($token);
        $this->save();
    }

    /**
     * Persist a token for all repos under a vendor/owner.
     */
    public function saveForOwner(string $registry, string $vendor, string $token): void
    {
        $this->ensureNode($registry, $vendor);
        $this->data['tokens'][$registry][$vendor]['global'] = CryptData::encrypt($token);
        $this->save();
    }

    /**
     * Persist a token for an entire registry.
     */
    public function saveForRegistry(string $registry, string $token): void
    {
        if (!isset($this->data['tokens'][$registry])) {
            $this->data['tokens'][$registry] = [];
        }
        $this->data['tokens'][$registry]['_registry_global'] = CryptData::encrypt($token);
        $this->save();
    }

    /**
     * Save using the Source's identity, honoring the requested scope.
     *
     * @param 'repo'|'owner'|'registry' $scope
     */
    public function saveFromSource(Source $source, string $token, string $scope = 'owner'): void
    {
        match ($scope) {
            'repo'     => $this->saveForRepo($source->registry, $source->vendor, $source->slug, $token),
            'registry' => $this->saveForRegistry($source->registry, $token),
            default    => $this->saveForOwner($source->registry, $source->vendor, $token),
        };
    }

    /**
     * Remove all stored tokens for a registry.
     */
    public function forget(string $registry): void
    {
        unset($this->data['tokens'][$registry]);
        $this->save();
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function load(): void
    {
        if (!is_file($this->path)) {
            $this->data = ['tokens' => []];
            return;
        }

        $raw = file_get_contents($this->path);
        $decoded = $raw !== false ? json_decode($raw, true) : null;

        $this->data = is_array($decoded) ? $decoded : ['tokens' => []];
    }

    private function save(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $this->data['updated_at'] = date('c');

        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->path, $json, LOCK_EX);
        chmod($this->path, 0600);
    }

    private function decrypt(string $value): ?string
    {
        return CryptData::decrypt($value);
    }

    private function ensureNode(string $registry, string $vendor): void
    {
        if (!isset($this->data['tokens'][$registry])) {
            $this->data['tokens'][$registry] = [];
        }
        if (!isset($this->data['tokens'][$registry][$vendor])) {
            $this->data['tokens'][$registry][$vendor] = ['global' => null, 'repos' => []];
        }
    }
}
