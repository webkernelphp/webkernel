<?php declare(strict_types=1);

namespace Webkernel;

use Illuminate\Support\Facades\Crypt;
use Exception;

final class CryptData
{
  public static function encrypt(string $value): string
  {
    return Crypt::encryptString($value);
  }

  public static function decrypt(string $value): ?string
  {
    try {
      return Crypt::decryptString($value);
    } catch (Exception) {
      return null;
    }
  }
}
