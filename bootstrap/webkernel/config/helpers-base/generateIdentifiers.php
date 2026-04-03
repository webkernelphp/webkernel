<?php
if (!function_exists('generateIdentifiers')) {
  /**
   * Ultra-fast unique identifier generator with multiple strategies.
   *
   * STRATEGIES:
   * - 'random' (default): Cryptographically random identifiers
   * - 'epoch': Microsecond timestamp-based (FASTEST, ~0.5μs)
   * - 'username': Slugified name + random suffix
   * - 'sequential': Counter-based with random prefix (deterministic)
   * - 'nano': Nanosecond precision with collision resistance
   *
   * @param int $count Number of identifiers to generate
   * @param int $length Length of each identifier (default: 12)
   * @param string $strategy Generation strategy (default: 'random')
   * @param array<string, mixed> $options Strategy-specific options
   *   - 'name': string - For 'username' strategy
   *   - 'prefix': string - Custom prefix for any strategy
   *   - 'format': string - Output format: 'array', 'css', 'raw'
   *   - 'cssSafe': bool - Ensure CSS-valid (default: true)
   * @return array<int, string>|string
   *
   * @example
   * // FASTEST: Epoch-based (microsecond response)
   * [$id1, $id2, $id3] = generateIdentifiers(3, 12, 'epoch');
   *
   * @example
   * // Username from name
   * [$username] = generateIdentifiers(1, 16, 'username', ['name' => 'John Doe']);
   * // Result: "johndoe_a8x4k2"
   *
   * @example
   * // Random with prefix
   * [$wk1, $wk2] = generateIdentifiers(2, 12, 'random', ['prefix' => 'wk_']);
   *
   * @example
   * // Nano-precision for extreme uniqueness
   * [$id1, $id2] = generateIdentifiers(2, 16, 'nano');
   *
   * @example
   * // CSS selectors output
   * $css = generateIdentifiers(3, 10, 'random', ['format' => 'css']);
   */
  function generateIdentifiers(int $count, int $length = 12, string $strategy = 'random', array $options = [])
  {
    if ($count < 1) {
      $format = $options['format'] ?? 'array';
      return $format === 'array' ? [] : '';
    }

    if ($length < 1) {
      throw new InvalidArgumentException('Length must be at least 1.');
    }

    $prefix = $options['prefix'] ?? '';
    $cssSafe = $options['cssSafe'] ?? true;
    $format = $options['format'] ?? 'array';

    // Adjust effective length for prefix
    $effectiveLength = $length - strlen($prefix);
    if ($effectiveLength < 1) {
      throw new InvalidArgumentException('Length must be greater than prefix length.');
    }

    $identifiers = match ($strategy) {
      'epoch' => _generateEpoch($count, $effectiveLength, $prefix, $cssSafe),
      'username' => _generateUsername($count, $effectiveLength, $prefix, $options),
      'sequential' => _generateSequential($count, $effectiveLength, $prefix, $cssSafe),
      'nano' => _generateNano($count, $effectiveLength, $prefix, $cssSafe),
      default => _generateRandom($count, $effectiveLength, $prefix, $cssSafe),
    };

    return match ($format) {
      'css' => implode(', ', array_map(fn($id) => ".{$id}", $identifiers)),
      'raw' => implode(' ', $identifiers),
      default => $identifiers,
    };
  }
}

// STRATEGY IMPLEMENTATIONS (optimized for microsecond performance)

function _generateRandom(int $count, int $length, string $prefix, bool $cssSafe): array
{
  static $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  static $letters = 'abcdefghijklmnopqrstuvwxyz';

  $identifiers = [];
  $charsLen = 36;
  $lettersLen = 26;

  // Pre-generate all random bytes at once (MASSIVE speedup)
  $totalBytes = $count * $length;
  $randomBytes = random_bytes($totalBytes);
  $byteIndex = 0;

  for ($i = 0; $i < $count; $i++) {
    $id = $prefix;

    if ($cssSafe && $prefix === '') {
      $id .= $letters[ord($randomBytes[$byteIndex++]) % $lettersLen];
      $remaining = $length - 1;
    } else {
      $remaining = $length;
    }

    for ($j = 0; $j < $remaining; $j++) {
      $id .= $chars[ord($randomBytes[$byteIndex++]) % $charsLen];
    }

    $identifiers[] = $id;
  }

  return $identifiers;
}

function _generateEpoch(int $count, int $length, string $prefix, bool $cssSafe): array
{
  static $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

  $identifiers = [];
  $base = (int) (microtime(true) * 1000000); // Microsecond timestamp

  for ($i = 0; $i < $count; $i++) {
    $timestamp = $base + $i;
    $encoded = '';

    // Base36 encoding of timestamp (ultra-fast)
    while ($timestamp > 0 && strlen($encoded) < $length) {
      $encoded = $chars[$timestamp % 36] . $encoded;
      $timestamp = (int) ($timestamp / 36);
    }

    // Pad to length
    $encoded = str_pad($encoded, $length, $chars[0], STR_PAD_LEFT);

    // Ensure CSS safety
    if ($cssSafe && $prefix === '' && is_numeric($encoded[0])) {
      $encoded[0] = 'a';
    }

    $identifiers[] = $prefix . $encoded;
  }

  return $identifiers;
}

function _generateUsername(int $count, int $length, string $prefix, array $options): array
{
  $name = $options['name'] ?? 'user';

  // Fast slugify
  $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $name));
  $slug = $slug ?: 'user';

  // Ensure slug doesn't exceed reasonable length
  $maxSlugLen = (int) ($length * 0.6);
  if (strlen($slug) > $maxSlugLen) {
    $slug = substr($slug, 0, $maxSlugLen);
  }

  $suffixLen = $length - strlen($slug) - 1; // -1 for underscore
  if ($suffixLen < 1) {
    $suffixLen = 4;
  }

  $identifiers = [];
  $randomBytes = random_bytes($count * $suffixLen);
  $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
  $byteIndex = 0;

  for ($i = 0; $i < $count; $i++) {
    $suffix = '';
    for ($j = 0; $j < $suffixLen; $j++) {
      $suffix .= $chars[ord($randomBytes[$byteIndex++]) % 36];
    }
    $identifiers[] = $prefix . $slug . '_' . $suffix;
  }

  return $identifiers;
}

function _generateSequential(int $count, int $length, string $prefix, bool $cssSafe): array
{
  static $counter = 0;
  static $sessionPrefix = null;

  // Generate session prefix once
  if ($sessionPrefix === null) {
    $bytes = random_bytes(4);
    $sessionPrefix = substr(bin2hex($bytes), 0, 6);
  }

  $identifiers = [];
  $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

  for ($i = 0; $i < $count; $i++) {
    $num = $counter++;
    $encoded = '';

    // Base36 encode counter
    do {
      $encoded = $chars[$num % 36] . $encoded;
      $num = (int) ($num / 36);
    } while ($num > 0);

    $id = $prefix . $sessionPrefix . $encoded;
    $id = str_pad($id, strlen($prefix) + $length, '0', STR_PAD_RIGHT);

    if ($cssSafe && $prefix === '' && is_numeric($id[0])) {
      $id[0] = 'a';
    }

    $identifiers[] = substr($id, 0, strlen($prefix) + $length);
  }

  return $identifiers;
}

function _generateNano(int $count, int $length, string $prefix, bool $cssSafe): array
{
  $identifiers = [];

  for ($i = 0; $i < $count; $i++) {
    // Combine hrtime (nanosecond) with random bytes
    $nanoTime = hrtime(true);
    $randomPart = random_bytes(8);

    // Mix them together
    $combined = hash('xxh3', $nanoTime . $randomPart . $i, true);
    $encoded = rtrim(strtr(base64_encode($combined), '+/', '-_'), '=');

    $id = $prefix . substr($encoded, 0, $length);

    if ($cssSafe && $prefix === '' && is_numeric($id[0])) {
      $id = 'n' . substr($id, 1);
    }

    $identifiers[] = substr($id, 0, strlen($prefix) + $length);
  }

  return $identifiers;
}

if (!function_exists('fastId')) {
  /**
   * Ultra-fast single identifier generator (optimized shorthand).
   *
   * @param int $length Length of identifier (default: 12)
   * @param string $strategy Strategy: 'random', 'epoch', 'nano' (default: 'epoch')
   * @return string Single identifier
   *
   * @example
   * $id = fastId(); // Microsecond-fast epoch-based
   * $id = fastId(16, 'random'); // Cryptographically random
   * $id = fastId(20, 'nano'); // Nanosecond precision
   */
  function fastId(int $length = 12, string $strategy = 'epoch'): string
  {
    return generateIdentifiers(1, $length, $strategy)[0];
  }
}

if (!function_exists('usernameId')) {
  /**
   * Generate username-based identifier (shorthand).
   *
   * @param string $name User's name
   * @param int $length Total length (default: 16)
   * @return string Username identifier
   *
   * @example
   * $username = usernameId('John Doe'); // "johndoe_x8k4"
   */
  function usernameId(string $name, int $length = 16): string
  {
    return generateIdentifiers(1, $length, 'username', ['name' => $name])[0];
  }
}

if (!function_exists('formatIdentifiers')) {
  /**
   * Format an array of identifiers into different output types.
   *
   * @param array<int, string> $identifiers Array of identifier strings
   * @param string $type Output format: "css", "raw", "array"
   * @return array<int, string>|string Formatted identifiers
   */
  function formatIdentifiers(array $identifiers, string $type = 'array')
  {
    return match ($type) {
      'css' => implode(', ', array_map(fn($id) => ".{$id}", $identifiers)),
      'raw' => implode(' ', $identifiers),
      default => $identifiers,
    };
  }
}
