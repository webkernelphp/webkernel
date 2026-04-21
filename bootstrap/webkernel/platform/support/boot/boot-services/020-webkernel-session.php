<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
//  § 2  WebkernelSession
// ═══════════════════════════════════════════════════════════════════
/**
 * Lightweight file-backed session for pre-boot flows.
 * State is stored as a HMAC-signed JSON file in the system temp
 * directory. No cookies, no PHP session.
 */
final class WebkernelSession
{
    private array $data  = [];
    private bool  $dirty = false;

    private function __construct(
        private readonly string     $token,
        private readonly HmacSigner $signer,
        private readonly string     $storePath,
    ) {}

    public static function load(string $token, HmacSigner $signer, ?string $storeDir = null): self
    {
        $dir      = ($storeDir ?? sys_get_temp_dir()) . '/webkernel_sessions';
        $path     = $dir . '/' . preg_replace('/[^a-zA-Z0-9\-_]/', '', $token) . '.sess';
        $instance = new self($token, $signer, $path);

        if (is_file($path)) {
            $raw  = @file_get_contents($path);
            $data = ($raw !== false) ? $signer->verifyArray($raw) : null;
            if (is_array($data)) {
                if (isset($data['__ts']) && (time() - $data['__ts']) < 7200) {
                    $instance->data = $data;
                } else {
                    @unlink($path);
                }
            }
        }
        return $instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        $this->dirty      = true;
        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function save(): bool
    {
        if (!$this->dirty) {
            return true;
        }
        $dir = dirname($this->storePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $this->data['__ts'] = time();
        $signed = $this->signer->signArray($this->data);
        return @file_put_contents($this->storePath, $signed, LOCK_EX) !== false;
    }

    public function destroy(): void
    {
        @unlink($this->storePath);
    }

    public function token(): string
    {
        return $this->token;
    }
}
