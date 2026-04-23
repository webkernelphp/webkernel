<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerService
{
    protected string $composerBinary;

    protected string $phpBinary;

    public function __construct()
    {
        $this->phpBinary = PHP_BINARY;
        $this->composerBinary = $this->resolveComposerBinary();
    }

    protected function resolveComposerBinary(): string
    {
        $configured = config('dependency-manager.composer_binary');
        if ($configured && $this->binaryExists($configured)) {
            return $configured;
        }

        $finder = new ExecutableFinder;
        $found = $finder->find('composer');
        if ($found) {
            return $found;
        }

        $localPhar = base_path('composer.phar');
        if (file_exists($localPhar)) {
            return $this->phpBinary . ' ' . $localPhar;
        }

        return 'composer';
    }

    protected function binaryExists(string $binary): bool
    {
        $process = new Process(['which', $binary]);
        $process->run();
        return $process->isSuccessful();
    }

    public function getOutdatedPackages(): array
    {
        return Cache::remember('filament-dependency-manager:composer-outdated', 3600, function () {
            if (!$this->ensureComposerExists()) {
                return [];
            }

            $process = new Process(
                [$this->composerBinary, 'outdated', '--format=json'],
                base_path()
            );

            $process->setEnv([
                'PATH' => dirname($this->phpBinary) . ':/usr/local/bin:/usr/bin:/bin',
                'HOME' => getenv('HOME') ?: '/root',
                'COMPOSER_HOME' => getenv('HOME') . '/.composer',
            ]);

            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful()) {
                return [];
            }

            $output = json_decode($process->getOutput(), true);

            return $output['installed'] ?? [];
        });
    }

    protected function ensureComposerExists(): bool
    {
        $process = new Process([$this->composerBinary, '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        return $this->downloadComposer();
    }

    protected function downloadComposer(): bool
    {
        $composerPath = base_path('composer.phar');

        if (file_exists($composerPath)) {
            $this->composerBinary = $this->phpBinary . ' ' . $composerPath;
            return true;
        }

        $downloadUrl = 'https://getcomposer.org/composer.phar';

        $process = new Process([
            'curl',
            '-fsSL',
            $downloadUrl,
            '-o',
            $composerPath,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !file_exists($composerPath)) {
            return false;
        }

        $this->composerBinary = $this->phpBinary . ' ' . $composerPath;
        return true;
    }

    public function clearCache(): void
    {
        Cache::forget('filament-dependency-manager:composer-outdated');
    }

    public function getRepositoryUrl(array $record): ?string
    {
        $source = $record['source'] ?? null;

        if (! $source) {
            return null;
        }

        $source = rtrim($source, '/');

        if (str_contains($source, '/tree/')) {
            return preg_replace('#/tree/[^/]+$#', '', $source);
        }

        return $source;
    }

    public function getReleaseUrl(array $record): ?string
    {
        $repositoryUrl = $this->getRepositoryUrl($record);
        $latest = $record['latest'] ?? null;

        if (! $repositoryUrl || ! $latest) {
            return null;
        }

        if (! str_contains($repositoryUrl, 'github.com')) {
            return $repositoryUrl;
        }

        return "{$repositoryUrl}/releases/tag/{$latest}";
    }

    public function getAllInstalledPackages(): array
    {
        return Cache::remember('filament-dependency-manager:composer-all', 3600, function () {
            $installedPath = base_path('vendor/composer/installed.json');

            if (!file_exists($installedPath)) {
                return [];
            }

            $installed = json_decode(file_get_contents($installedPath), true);
            if (!isset($installed['packages'])) {
                return [];
            }

            $outdated = $this->getOutdatedPackages();
            $reverseDeps = $this->buildReverseDependencyMap($installed['packages']);
            $outdatedMap = collect($outdated)->keyBy('name')->toArray();

            return collect($installed['packages'])
                ->map(function (array $package) use ($outdatedMap, $reverseDeps) {
                    $name = $package['name'] ?? '';
                    $version = $package['version'] ?? '0.0.0';

                    $outdatedInfo = $outdatedMap[$name] ?? null;
                    $hasUpdate = $outdatedInfo !== null;

                    $deps = $reverseDeps[$name] ?? [];
                    return [
                        'name' => $name,
                        'version' => $version,
                        'description' => $package['description'] ?? '',
                        'latest' => $outdatedInfo['latest'] ?? $version,
                        'latest-status' => $outdatedInfo['latest-status'] ?? 'up-to-date',
                        'type' => 'dependency',
                        'required_by' => implode(',', $deps),
                        'has_update' => $hasUpdate,
                    ];
                })
                ->sortBy('name')
                ->values()
                ->toArray();
        });
    }

    protected function buildReverseDependencyMap(array $packages): array
    {
        $map = [];

        foreach ($packages as $package) {
            $name = $package['name'] ?? '';
            if (!$name) {
                continue;
            }

            $requires = array_merge(
                $package['require'] ?? [],
                $package['require-dev'] ?? []
            );

            foreach (array_keys($requires) as $required) {
                if (!isset($map[$required])) {
                    $map[$required] = [];
                }
                if (!in_array($name, $map[$required])) {
                    $map[$required][] = $name;
                }
            }
        }

        return $map;
    }
}
