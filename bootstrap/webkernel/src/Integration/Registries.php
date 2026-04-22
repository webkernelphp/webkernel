<?php declare(strict_types=1);

namespace Webkernel\Integration;

final class Registries
{
    public const REGISTRY_OPTIONS = [
        'github-com'           => 'GitHub',
        'gitlab-com'           => 'GitLab',
        'webkernelphp-com'     => 'Webkernel Registry',
        'git-numerimondes-com' => 'Numerimondes',
    ];

    public const REGISTRY_DEFAULTS_CLI = [
        'webkernelphp.com' => 'webkernelphp.com',
        'github.com'       => 'github.com',
        'gitlab.com'       => 'gitlab.com',
        'custom'           => 'Custom',
    ];

    public static function options(): array
    {
        return self::REGISTRY_OPTIONS;
    }

    public static function defaultOption(): string
    {
        return 'github-com';
    }
}
