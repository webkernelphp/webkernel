<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class NpmService
{
    protected string $binary;

    protected string $phpBinary;

    protected string $client;

    public function __construct()
    {
        $this->phpBinary = PHP_BINARY;
        $this->client = config('dependency-manager.npm_client', 'npm');
        $this->binary = $this->resolveNpmBinary();
    }

    protected function resolveNpmBinary(): string
    {
        $configured = config('dependency-manager.npm_binary');
        if ($configured && $this->binaryExists($configured)) {
            return $configured;
        }

        $finder = new ExecutableFinder;
        $found = $finder->find($this->client);
        if ($found) {
            return $found;
        }

        return $this->client;
    }

    protected function binaryExists(string $binary): bool
    {
        $process = new Process(['which', $binary]);
        $process->run();
        return $process->isSuccessful();
    }

    public function getOutdatedPackages(): array
    {
        return Cache::remember('filament-dependency-manager:npm-outdated', 3600, function () {
            $command = $this->buildCommand();

            $process = new Process(
                $command,
                base_path()
            );

            $process->setEnv([
                'PATH' => dirname($this->phpBinary) . ':/usr/local/bin:/usr/bin:/bin',
                'HOME' => getenv('HOME') ?: '/root',
            ]);

            $process->setTimeout(60);
            $process->run();

            // npm outdated exits with code 1 when there are outdated packages — not an error
            if ($process->getExitCode() > 1) {
                return [];
            }

            $output = json_decode($process->getOutput(), true);

            if (! is_array($output)) {
                return [];
            }

            return $this->normalize($output);
        });
    }

    public function clearCache(): void
    {
        Cache::forget('filament-dependency-manager:npm-outdated');
    }

    protected function buildCommand(): array
    {
        return match ($this->client) {
            'pnpm' => [$this->binary, 'outdated', '--format', 'json'],
            'yarn' => [$this->binary, 'outdated', '--json'],
            default => [$this->binary, 'outdated', '--json'],
        };
    }

    protected function normalize(array $output): array
    {
        return collect($output)
            ->map(function (array $package, string $name) {
                $current = $package['current'] ?? '—';
                $latest = $package['latest'] ?? $package['wanted'] ?? '—';

                return [
                    'name' => $name,
                    'version' => $current,
                    'latest' => $latest,
                    'latest-status' => $this->resolveStatus($current, $latest),
                    'type' => $package['type'] ?? 'devDependencies',
                    'source' => null,
                    'description' => $package['location'] ?? null,
                ];
            })
            ->values()
            ->toArray();
    }

    protected function resolveStatus(string $current, string $latest): string
    {
        if ($current === $latest || $current === '—') {
            return 'up-to-date';
        }

        $currentParts = explode('.', ltrim($current, '^~v'));
        $latestParts = explode('.', ltrim($latest, '^~v'));

        if (($currentParts[0] ?? 0) !== ($latestParts[0] ?? 0)) {
            return 'update-possible'; // major
        }

        return 'semver-safe-update'; // minor/patch
    }

    public function getAllInstalledPackages(): array
    {
        return Cache::remember('filament-dependency-manager:npm-all', 3600, function () {
            $lockPath = base_path('package-lock.json');

            if (!file_exists($lockPath)) {
                return [];
            }

            $lock = json_decode(file_get_contents($lockPath), true);
            if (!isset($lock['packages'])) {
                return [];
            }

            $outdated = $this->getOutdatedPackages();
            $reverseDeps = $this->buildReverseDependencyMap($lock['packages']);
            $outdatedMap = collect($outdated)->keyBy('name')->toArray();

            $packages = [];

            foreach ($lock['packages'] as $packagePath => $packageInfo) {
                if (empty($packagePath) || $packagePath === '') {
                    continue;
                }

                $name = $packageInfo['name'] ?? $packagePath;
                $version = $packageInfo['version'] ?? '0.0.0';

                $outdatedInfo = $outdatedMap[$name] ?? null;
                $hasUpdate = $outdatedInfo !== null;

                $deps = $reverseDeps[$name] ?? [];
                $packages[] = [
                    'name' => $name,
                    'version' => $version,
                    'description' => $packageInfo['description'] ?? '',
                    'latest' => $outdatedInfo['latest'] ?? $version,
                    'latest-status' => $outdatedInfo['latest-status'] ?? 'up-to-date',
                    'type' => $packageInfo['dev'] ? 'devDependencies' : 'dependencies',
                    'required_by' => implode(',', $deps),
                    'has_update' => $hasUpdate,
                ];
            }

            return collect($packages)
                ->sortBy('name')
                ->values()
                ->toArray();
        });
    }

    protected function buildReverseDependencyMap(array $packages): array
    {
        $map = [];

        foreach ($packages as $packagePath => $packageInfo) {
            $name = $packageInfo['name'] ?? $packagePath;
            if (!$name) {
                continue;
            }

            $requires = $packageInfo['dependencies'] ?? [];

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
