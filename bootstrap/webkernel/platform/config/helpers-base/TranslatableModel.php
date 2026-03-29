<?php

namespace Webkernel\Aptitudes\Builders\Website\Helpers;

/**
 * TranslatableModel Helper
 *
 * Provides static methods for handling JSON translations on Eloquent models.
 * Works with the translation format: content.translations.{locale}.{field}
 *
 * This helper is model-agnostic - it operates on arrays/data directly.
 * Models can use these methods without needing a trait.
 *
 * Usage in Model:
 * ```php
 * use Webkernel\Aptitudes\Builders\Website\Builder\Helpers\TranslatableModel;
 *
 * public function getTitleAttribute(): ?string
 * {
 *     return TranslatableModel::get($this->content, 'title');
 * }
 *
 * public function getTranslation(string $field, ?string $locale = null): ?string
 * {
 *     return TranslatableModel::get($this->content, $field, $locale);
 * }
 * ```
 */
namespace Webkernel\Aptitudes\Builders\Website\Helpers;

class TranslatableModel
{
  /**
   * Get a translated field value from content array
   *
   * @param array|null $content The content array containing translations
   * @param string $field The field name to retrieve
   * @param string|null $locale Locale code (defaults to app locale)
   * @param string|null $fallbackLocale Fallback locale if translation missing
   * @return mixed
   */
  public static function get(
    ?array $content,
    string $field,
    ?string $locale = null,
    ?string $fallbackLocale = null,
  ): mixed {
    if ($content === null) {
      return null;
    }

    $locale = $locale ?? app()->getLocale();
    $fallbackLocale = $fallbackLocale ?? config('app.fallback_locale', 'en');

    // Try current locale
    $value = $content['translations'][$locale][$field] ?? null;

    // Try fallback locale if not found
    if ($value === null && $locale !== $fallbackLocale) {
      $value = $content['translations'][$fallbackLocale][$field] ?? null;
    }

    // Try first available locale as last resort
    if ($value === null && !empty($content['translations'])) {
      $firstLocale = array_key_first($content['translations']);
      $value = $content['translations'][$firstLocale][$field] ?? null;
    }

    return $value;
  }

  /**
   * Set a translated field value in content array
   *
   * @param array $content The content array (passed by reference)
   * @param string $field The field name to set
   * @param mixed $value The value to set
   * @param string|null $locale Locale code (defaults to app locale)
   * @return array The modified content array
   */
  public static function set(array &$content, string $field, mixed $value, ?string $locale = null): array
  {
    $locale = $locale ?? app()->getLocale();

    if (!isset($content['translations'])) {
      $content['translations'] = [];
    }

    if (!isset($content['translations'][$locale])) {
      $content['translations'][$locale] = [];
    }

    $content['translations'][$locale][$field] = $value;

    return $content;
  }

  /**
   * Check if a translation exists for a field
   *
   * @param array|null $content The content array
   * @param string $field The field name
   * @param string|null $locale Locale code (defaults to app locale)
   * @return bool
   */
  public static function has(?array $content, string $field, ?string $locale = null): bool
  {
    if ($content === null) {
      return false;
    }

    $locale = $locale ?? app()->getLocale();

    return isset($content['translations'][$locale][$field]);
  }

  /**
   * Get all translations for a specific field across all locales
   *
   * @param array|null $content The content array
   * @param string $field The field name
   * @return array<string, mixed> [locale => value]
   */
  public static function getAllForField(?array $content, string $field): array
  {
    if ($content === null || !isset($content['translations'])) {
      return [];
    }

    $result = [];
    foreach ($content['translations'] as $locale => $fields) {
      if (isset($fields[$field])) {
        $result[$locale] = $fields[$field];
      }
    }

    return $result;
  }

  /**
   * Get all translations for a specific locale
   *
   * @param array|null $content The content array
   * @param string|null $locale Locale code (defaults to app locale)
   * @return array<string, mixed> [field => value]
   */
  public static function getAllForLocale(?array $content, ?string $locale = null): array
  {
    if ($content === null) {
      return [];
    }

    $locale = $locale ?? app()->getLocale();

    return $content['translations'][$locale] ?? [];
  }

  /**
   * Get all available locales from content
   *
   * @param array|null $content The content array
   * @return array<string>
   */
  public static function getAvailableLocales(?array $content): array
  {
    if ($content === null || !isset($content['translations'])) {
      return [];
    }

    return array_keys($content['translations']);
  }

  /**
   * Remove a translation for a field in a specific locale
   *
   * @param array $content The content array (passed by reference)
   * @param string $field The field name
   * @param string|null $locale Locale code (defaults to app locale)
   * @return array The modified content array
   */
  public static function remove(array &$content, string $field, ?string $locale = null): array
  {
    $locale = $locale ?? app()->getLocale();

    if (isset($content['translations'][$locale][$field])) {
      unset($content['translations'][$locale][$field]);
    }

    return $content;
  }

  /**
   * Remove all translations for a specific locale
   *
   * @param array $content The content array (passed by reference)
   * @param string $locale Locale code
   * @return array The modified content array
   */
  public static function removeLocale(array &$content, string $locale): array
  {
    if (isset($content['translations'][$locale])) {
      unset($content['translations'][$locale]);
    }

    return $content;
  }

  /**
   * Merge translations from another content array
   *
   * @param array $content The target content array (passed by reference)
   * @param array $source The source content array to merge from
   * @param bool $overwrite Whether to overwrite existing translations
   * @return array The modified content array
   */
  public static function merge(array &$content, array $source, bool $overwrite = false): array
  {
    if (!isset($source['translations'])) {
      return $content;
    }

    if (!isset($content['translations'])) {
      $content['translations'] = [];
    }

    foreach ($source['translations'] as $locale => $fields) {
      if (!isset($content['translations'][$locale])) {
        $content['translations'][$locale] = [];
      }

      foreach ($fields as $field => $value) {
        if ($overwrite || !isset($content['translations'][$locale][$field])) {
          $content['translations'][$locale][$field] = $value;
        }
      }
    }

    return $content;
  }

  /**
   * Create a fresh content structure with translations
   *
   * @param array<string, array<string, mixed>> $translations [locale => [field => value]]
   * @return array
   */
  public static function create(array $translations): array
  {
    return ['translations' => $translations];
  }

  /**
   * Get excerpt from a field (useful for description/content fields)
   *
   * @param array|null $content The content array
   * @param string $field The field name
   * @param int $limit Character limit
   * @param string $end Ending string (e.g., '...')
   * @param string|null $locale Locale code
   * @return string|null
   */
  public static function excerpt(
    ?array $content,
    string $field,
    int $limit = 160,
    string $end = '...',
    ?string $locale = null,
  ): ?string {
    $value = static::get($content, $field, $locale);

    if ($value === null) {
      return null;
    }

    // Strip HTML tags
    $value = strip_tags((string) $value);

    // Return if within limit
    if (mb_strlen($value) <= $limit) {
      return $value;
    }

    // Truncate and add ending
    return rtrim(mb_substr($value, 0, $limit)) . $end;
  }
}
