<?php

namespace Webkernel\Aptitudes\Builders\Website\Helpers;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Webkernel\Aptitudes\Builders\Website\Domain\Models\ContentType;
use Webkernel\Aptitudes\I18n\QueryLanguages;

/**
 * TranslatableForm Helper
 *
 * Provides methods for building translatable Filament forms.
 * Stores translations in JSON format: translations.{locale}.{field}
 *
 * This helper is locale-agnostic - locales must be passed as parameters.
 * The caller decides where locales come from (Site, config, CMS, etc.)
 *
 * Usage:
 * ```php
 * use Webkernel\Aptitudes\Builders\Website\Builder\Helpers\TranslatableForm;
 *
 * // Locales from Site (Website Builder)
 * $locales = $site->getSiteLocales();
 *
 * // Or from config (CMS)
 * $locales = config('webkernel-cms.active_locales');
 *
 * TranslatableForm::tabs(
 *     fn(string $locale) => [
 *         TextInput::make('title')->required(),
 *         Textarea::make('description'),
 *     ],
 *     $locales,
 *     $locales[0] ?? 'en'
 * );
 * ```
 */
namespace Webkernel\Aptitudes\Builders\Website\Helpers;

class TranslatableForm
{
  /**
   * Cache for content type locales
   *
   * @var array<string, array{locales: array<string>, default: string}>
   */
  protected static array $contentTypeLocalesCache = [];

  /**
   * Get locales from a ContentType by handle
   *
   * @param string $handle Content type handle (e.g., 'etw.providers', 'etw.services')
   * @return array{locales: array<string>, default: string, labels: array<string, string>}
   */
  public static function getLocalesFromContentType(string $handle): array
  {
    // Check cache
    if (isset(self::$contentTypeLocalesCache[$handle])) {
      return self::$contentTypeLocalesCache[$handle];
    }

    // Find content type
    $contentType = ContentType::where('handle', $handle)->first();

    if ($contentType) {
      $locales = $contentType->getContentTypeLocales();
      $default = $contentType->getDefaultLocale();
      $labels = QueryLanguages::make()->only($locales)->pluck('name', 'code');
    } else {
      // Fallback to config if content type not found
      $locales = config('webkernel-cms.active_locales', ['en']);
      $default = $locales[0] ?? 'en';
      $labels = QueryLanguages::make()->only($locales)->pluck('name', 'code');
    }

    $result = [
      'locales' => $locales,
      'default' => $default,
      'labels' => $labels,
    ];

    self::$contentTypeLocalesCache[$handle] = $result;

    return $result;
  }

  /**
   * Create translatable tabs using ContentType locales
   *
   * @param callable $schemaCallback Callback receiving locale, returns array of form fields
   * @param string $contentTypeHandle Content type handle (e.g., 'etw.providers')
   * @return Tabs
   */
  public static function tabsFromContentType(callable $schemaCallback, string $contentTypeHandle): Tabs
  {
    $config = static::getLocalesFromContentType($contentTypeHandle);

    return static::tabs($schemaCallback, $config['locales'], $config['default'], $config['labels']);
  }

  /**
   * Create translatable fieldset using ContentType locales
   *
   * @param callable $schemaCallback Callback receiving locale, returns array of form fields
   * @param string $contentTypeHandle Content type handle (e.g., 'etw.providers')
   * @param string|null $label Fieldset label
   * @return Fieldset
   */
  public static function fieldsetFromContentType(
    callable $schemaCallback,
    string $contentTypeHandle,
    ?string $label = null,
  ): Fieldset {
    $config = static::getLocalesFromContentType($contentTypeHandle);

    return static::fieldset($schemaCallback, $config['locales'], $label, $config['labels']);
  }

  /**
   * Create translatable wizard using ContentType locales
   *
   * @param callable $schemaCallback Callback receiving locale, returns array of form fields
   * @param string $contentTypeHandle Content type handle (e.g., 'etw.providers')
   * @return Wizard
   */
  public static function wizardFromContentType(callable $schemaCallback, string $contentTypeHandle): Wizard
  {
    $config = static::getLocalesFromContentType($contentTypeHandle);

    return static::wizard($schemaCallback, $config['locales'], $config['default'], $config['labels']);
  }

  /**
   * Clear the content type locales cache
   */
  public static function clearCache(): void
  {
    self::$contentTypeLocalesCache = [];
  }

  /**
   * Create translatable tabs
   *
   * Best for: Complex forms with multiple translatable fields
   *
   * @param callable $schemaCallback Callback receiving locale, returns array of form fields
   * @param array<string> $locales List of locale codes (e.g., ['en', 'fr'])
   * @param string|null $defaultLocale Default locale code (first in array if null)
   * @param array<string, string>|null $localeLabels Optional map of locale => label
   * @return Tabs
   */
  public static function tabs(
    callable $schemaCallback,
    array $locales,
    ?string $defaultLocale = null,
    ?array $localeLabels = null,
  ): Tabs {
    if (empty($locales)) {
      $locales = ['en'];
    }

    $defaultLocale = $defaultLocale ?? $locales[0];

    $tabs = [];
    foreach ($locales as $locale) {
      $isDefault = $locale === $defaultLocale;
      $localeName = $localeLabels[$locale] ?? strtoupper($locale);

      $wrappedSchema = array_map(
        fn($field) => $field->statePath("translations.{$locale}." . $field->getName()),
        $schemaCallback($locale),
      );

      $tabs[] = Tab::make($localeName)
        ->schema($wrappedSchema)
        ->badge($isDefault ? 'Default' : null);
    }

    return Tabs::make('translations')->tabs($tabs)->contained(false);
  }

  /**
   * Create translatable fieldset (inline mode)
   *
   * Best for: 1-2 fields that need translation, keeps form compact
   *
   * @param callable $schemaCallback Callback receiving locale, returns array of form fields
   * @param array<string> $locales List of locale codes
   * @param string|null $label Fieldset label
   * @param array<string, string>|null $localeLabels Optional map of locale => label
   * @return Fieldset
   */
  public static function fieldset(
    callable $schemaCallback,
    array $locales,
    ?string $label = null,
    ?array $localeLabels = null,
  ): Fieldset {
    if (empty($locales)) {
      $locales = ['en'];
    }

    $allFields = [];
    foreach ($locales as $locale) {
      $localeName = $localeLabels[$locale] ?? strtoupper($locale);

      $fields = $schemaCallback($locale);
      foreach ($fields as $field) {
        $wrappedField = clone $field;
        $wrappedField->statePath("translations.{$locale}." . $field->getName());
        $wrappedField->label("{$field->getLabel()} ({$localeName})");
        $allFields[] = $wrappedField;
      }
    }

    return Fieldset::make($label ?? 'Translations')
      ->schema($allFields)
      ->columns(count($locales));
  }

  /**
   * Create translatable wizard (step-by-step mode)
   *
   * Best for: Complex forms with many translatable sections
   *
   * @param callable $schemaCallback Callback receiving locale, returns array of form fields
   * @param array<string> $locales List of locale codes
   * @param string|null $defaultLocale Default locale code
   * @param array<string, string>|null $localeLabels Optional map of locale => label
   * @return Wizard
   */
  public static function wizard(
    callable $schemaCallback,
    array $locales,
    ?string $defaultLocale = null,
    ?array $localeLabels = null,
  ): Wizard {
    if (empty($locales)) {
      $locales = ['en'];
    }

    $defaultLocale = $defaultLocale ?? $locales[0];

    $steps = [];
    foreach ($locales as $locale) {
      $isDefault = $locale === $defaultLocale;
      $localeName = $localeLabels[$locale] ?? strtoupper($locale);

      $wrappedSchema = array_map(
        fn($field) => $field->statePath("translations.{$locale}." . $field->getName()),
        $schemaCallback($locale),
      );

      $steps[] = Step::make($localeName)
        ->description($isDefault ? 'Default language' : "Translation for {$localeName}")
        ->schema($wrappedSchema);
    }

    return Wizard::make($steps)->contained(false)->skippable();
  }

  /**
   * Extract locale data from nested structure: translations.{locale}.{field}
   *
   * @param array<string, mixed> $data Data from database
   * @param string $locale Current locale
   * @return array<string, mixed> Flattened data with locale-specific values
   */
  public static function extractLocaleData(array $data, string $locale): array
  {
    $result = [];

    // Extract translations for current locale
    if (isset($data['translations'][$locale]) && is_array($data['translations'][$locale])) {
      foreach ($data['translations'][$locale] as $key => $value) {
        $result[$key] = $value;
      }
    }

    // Keep non-translation fields and process nested arrays
    foreach ($data as $key => $value) {
      if ($key === 'translations') {
        continue;
      }

      if (is_array($value)) {
        $result[$key] = static::extractLocaleDataFromArray($value, $locale);
      } else {
        $result[$key] = $value;
      }
    }

    return $result;
  }

  /**
   * Recursively extract locale data from nested arrays
   *
   * @param array<string, mixed> $array
   * @param string $locale
   * @return array<string, mixed>
   */
  private static function extractLocaleDataFromArray(array $array, string $locale): array
  {
    $result = [];

    foreach ($array as $key => $value) {
      if ($key === 'translations' && is_array($value)) {
        if (isset($value[$locale]) && is_array($value[$locale])) {
          foreach ($value[$locale] as $transKey => $transValue) {
            $result[$transKey] = $transValue;
          }
        }
      } elseif (is_array($value)) {
        $result[$key] = static::extractLocaleDataFromArray($value, $locale);
      } else {
        $result[$key] = $value;
      }
    }

    return $result;
  }

  /**
   * Mutate form data before saving to database
   *
   * Merges translation fields into the content column JSON structure
   *
   * @param array<string, mixed> $data Form data
   * @param string $contentColumn Name of the JSON column storing translations
   * @return array<string, mixed> Mutated data ready for database
   */
  public static function mutateForSave(array $data, string $contentColumn = 'content'): array
  {
    // If translations key exists, merge it into content column
    if (isset($data['translations'])) {
      $data[$contentColumn] = array_merge($data[$contentColumn] ?? [], ['translations' => $data['translations']]);
      unset($data['translations']);
    }

    return $data;
  }

  /**
   * Hydrate form data from database model
   *
   * Extracts translations from content column back to form structure
   *
   * @param array<string, mixed>|object $data Model data or array
   * @param string $contentColumn Name of the JSON column storing translations
   * @return array<string, mixed> Hydrated data for form
   */
  public static function hydrateFromModel(array|object $data, string $contentColumn = 'content'): array
  {
    $data = is_object($data) ? (array) $data : $data;

    // Extract translations from content column
    if (isset($data[$contentColumn]['translations'])) {
      $data['translations'] = $data[$contentColumn]['translations'];
    }

    return $data;
  }
}
