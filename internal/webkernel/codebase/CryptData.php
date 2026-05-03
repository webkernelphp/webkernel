<?php declare(strict_types=1);
namespace Webkernel;

use Illuminate\Support\Facades\Crypt;
use Exception;

/**
 * Thin static wrapper around Laravel's Crypt facade providing
 * a stable, minimal encryption surface for Webkernel internals.
 * Keeps all encryption calls in one place so the underlying
 * driver can be swapped without touching call sites.
 *
 * @method static bool supported(string $key, string $cipher)
 * @method static string generateKey(string $cipher)
 * @method static string encrypt(mixed $value, bool $serialize = true)
 * @method static string encryptString(string $value)
 * @method static mixed decrypt(string $payload, bool $unserialize = true)
 * @method static string decryptString(string $payload)
 * @method static bool appearsEncrypted(mixed $value)
 * @method static string getKey()
 * @method static array getAllKeys()
 * @method static array getPreviousKeys()
 * @method static \Illuminate\Encryption\Encrypter previousKeys(array $keys)
 *
 * @see \Illuminate\Encryption\Encrypter
 */
final class CryptData
{
    /**
     * Encrypts a plain-text string using Laravel's application key.
     * The result is a base64-encoded, MAC-signed ciphertext string
     * safe to store in the database or pass through untrusted channels.
     *
     * @param string $value The plain-text value to encrypt.
     * @return string       The encrypted, serialised ciphertext.
     */
    public static function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypts a ciphertext string previously produced by encrypt().
     * Returns null instead of throwing when decryption fails, covering
     * cases such as a rotated application key, tampered payload, or
     * a value that was never encrypted in the first place.
     *
     * @param string $value The ciphertext to decrypt.
     * @return string|null  The original plain-text, or null on failure.
     */
    public static function decrypt(string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (Exception) {
            return null;
        }
    }
}
