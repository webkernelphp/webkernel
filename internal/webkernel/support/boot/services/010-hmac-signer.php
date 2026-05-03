<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
//  § 1  HmacSigner
// ═══════════════════════════════════════════════════════════════════
final class HmacSigner
{
    private string $secret;
    private string $algo;

    public function __construct(string $secret, string $algo = 'sha256')
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('HmacSigner secret must not be empty.');
        }
        $this->secret = $secret;
        $this->algo   = $algo;
    }

    /**
     * Derive a self-contained opaque token from arbitrary context data.
     * The token is URL-safe base64, unguessable, tied to this server's secret.
     */
    public function token(string ...$parts): string
    {
        /** @disregard */
        $payload = implode('|', $parts) . '|' . microtime(true) . '|' . random_int(0, PHP_INT_MAX);
        $raw     = hash_hmac($this->algo, $payload, $this->secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** Sign a payload string. Returns "payload.signature". */
    public function sign(string $payload): string
    {
        $sig = hash_hmac($this->algo, $payload, $this->secret);
        return $payload . '.' . $sig;
    }

    /** Verify and extract payload from a signed string. Returns null on tamper. */
    public function verify(string $signed): ?string
    {
        $pos = strrpos($signed, '.');
        if ($pos === false) {
            return null;
        }
        $payload  = substr($signed, 0, $pos);
        $sig      = substr($signed, $pos + 1);
        $expected = hash_hmac($this->algo, $payload, $this->secret);
        return hash_equals($expected, $sig) ? $payload : null;
    }

    /** Sign an arbitrary array as JSON. Returns opaque signed blob. */
    public function signArray(array $data): string
    {
        return $this->sign(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /** Verify and decode a signed JSON array. Returns null on tamper/invalid. */
    public function verifyArray(string $signed): ?array
    {
        $json = $this->verify($signed);
        if ($json === null) {
            return null;
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    /** Compute a bare HMAC hex string for arbitrary data. */
    public function compute(string $data): string
    {
        return hash_hmac($this->algo, $data, $this->secret);
    }
}
