<?php declare(strict_types=1);

namespace Webkernel\Integration;

enum Registries: string
{
    case WebkernelRegistry = 'webkernelphp-com';
    case GitHub = 'github-com';
    case GitLab = 'gitlab-com';
    case Numerimondes = 'git-numerimondes-com';
    case Custom = 'custom';

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

    public function isMarketplace(): bool
    {
        return $this === self::WebkernelRegistry;
    }

    public function isCustom(): bool
    {
        return in_array($this, [self::GitHub, self::GitLab, self::Custom]);
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->value, self::cases()),
            array_map(fn ($case) => $case->label(), self::cases()),
        );
    }

    public static function customOptions(): array
    {
        $cases = [self::GitHub, self::GitLab, self::Custom];
        return array_combine(
            array_map(fn ($case) => $case->value, $cases),
            array_map(fn ($case) => $case->label(), $cases),
        );
    }

    public static function cliOptions(): array
    {
        return [
            'webkernelphp.com' => 'webkernelphp.com',
            'github.com'       => 'github.com',
            'gitlab.com'       => 'gitlab.com',
            'custom'           => 'Custom',
        ];
    }

    public static function defaultOption(): string
    {
        return self::GitHub->value;
    }

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

    public static function fromDomain(string $domain): ?self
    {
        return match (strtolower(trim($domain))) {
            'webkernelphp.com'   => self::WebkernelRegistry,
            'github.com'         => self::GitHub,
            'gitlab.com'         => self::GitLab,
            'git.numerimondes.com' => self::Numerimondes,
            default              => null,
        };
    }
}
