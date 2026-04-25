<?php declare(strict_types=1);
namespace Webkernel;

/**
 * Represents all supported package registry sources that Webkernel can
 * pull packages from. Each case maps to a stable string identifier used
 * in config files, CLI prompts, and API payloads.
 */
enum Registries: string
{
    case WebkernelRegistry = 'webkernelphp-com';
    case GitHub            = 'github-com';
    case GitLab            = 'gitlab-com';
    case Numerimondes      = 'git-numerimondes-com';
    case Custom            = 'custom';

    /**
     * Returns the human-readable display name for this registry,
     * suitable for use in UI labels, select boxes, and CLI output.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::WebkernelRegistry => 'Webkernel Registry',
            self::GitHub            => 'GitHub',
            self::GitLab            => 'GitLab',
            self::Numerimondes      => 'Numerimondes',
            self::Custom            => 'Custom',
        };
    }

    /**
     * Returns true when this registry is the official Webkernel marketplace.
     * Used to gate marketplace-specific features such as licence validation
     * and verified package badges.
     *
     * @return bool
     */
    public function isMarketplace(): bool
    {
        return $this === self::WebkernelRegistry;
    }

    /**
     * Returns true when this registry requires custom connection settings
     * (host, token, etc.) provided by the user rather than preconfigured
     * defaults. Covers GitHub, GitLab, and fully custom registries.
     *
     * @return bool
     */
    public function isCustom(): bool
    {
        return in_array($this, [self::GitHub, self::GitLab, self::Custom]);
    }

    /**
     * Builds a value => label map of every available registry case.
     * Intended for use in form select fields where all registries
     * should be presented as options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn(self $case): string => $case->value, self::cases()),
            array_map(fn(self $case): string => $case->label(), self::cases()),
        );
    }

    /**
     * Builds a value => label map restricted to the registries that require
     * custom user-supplied configuration (GitHub, GitLab, Custom).
     * Use this when the Webkernel marketplace option should be excluded
     * from the selection, e.g. during self-hosted setup wizards.
     *
     * @return array<string, string>
     */
    public static function customOptions(): array
    {
        $cases = [self::GitHub, self::GitLab, self::Custom];

        return array_combine(
            array_map(fn(self $case): string => $case->value, $cases),
            array_map(fn(self $case): string => $case->label(), $cases),
        );
    }

    /**
     * Returns the registry choices formatted for interactive CLI prompts.
     * Keys and values are Markdown-style links or plain strings as expected
     * by the installer's terminal UI component.
     *
     * @return array<string, string>
     */
    public static function cliOptions(): array
    {
        return [
            'webkernelphp.com' => 'webkernelphp.com',
            'github.com'       => 'github.com',
            'gitlab.com'       => 'gitlab.com',
            'custom'           => 'Custom',
        ];
    }

    /**
     * Returns the registry case value that should be pre-selected when no
     * explicit choice has been made by the user. Currently defaults to GitHub
     * as the most common self-hosted package source.
     *
     * @return string
     */
    public static function defaultOption(): string
    {
        return self::GitHub->value;
    }

    /**
     * Converts this registry case to its canonical domain string.
     * Used when constructing package URLs or displaying the active
     * registry host to the user.
     *
     * @return string
     */
    public function toDomain(): string
    {
        return match ($this) {
            self::WebkernelRegistry => 'webkernelphp.com',
            self::GitHub            => 'github.com',
            self::GitLab            => 'gitlab.com',
            self::Numerimondes      => 'git.numerimondes.com',
            self::Custom            => 'custom',
        };
    }

    /**
     * Attempts to resolve a registry case from a raw domain string.
     * Normalises the input by trimming whitespace and lowercasing before
     * matching, so minor formatting differences do not cause a miss.
     * Returns null when the domain does not correspond to any known registry,
     * leaving the caller to decide how to handle unknown sources.
     *
     * @param string $domain The domain to look up, e.g. "github.com".
     * @return self|null     The matching registry case, or null if unrecognised.
     */
    public static function fromDomain(string $domain): ?self
    {
        return match (strtolower(trim($domain))) {
            'webkernelphp.com'     => self::WebkernelRegistry,
            'github.com'           => self::GitHub,
            'gitlab.com'           => self::GitLab,
            'git.numerimondes.com' => self::Numerimondes,
            default                => null,
        };
    }
}
