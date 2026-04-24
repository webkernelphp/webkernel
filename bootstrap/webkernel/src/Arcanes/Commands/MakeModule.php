<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Commands;

use Illuminate\Console\Command;
use Throwable;
use Webkernel\Arcanes\Matrix\ArtifactMatrix;
use Webkernel\Arcanes\Matrix\NamingHelper;
use Webkernel\Arcanes\Scaffold\ScaffoldParams;
use Webkernel\Arcanes\Scaffold\ScaffoldResult;
use Webkernel\Arcanes\Scaffold\Scaffolder;
use Webkernel\Integration\Registries;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

/**
 * Interactive scaffolder. Collects input, builds ScaffoldParams, delegates to Scaffolder.
 * Zero path knowledge, zero manifest keys — all from ArtifactMatrix.
 */
class MakeModule extends Command
{
    protected $signature   = 'webkernel:make-module';
    protected $description = 'Scaffold a new Webkernel module or aptitude (dev mode)';

    public function handle(): int
    {
        info('Webkernel Module Generator');
        $type = $this->promptType();
        return match ($type) {
            'aptitude' => $this->runScaffold($this->aptitudeParams()),
            default    => $this->runScaffold($this->moduleParams()),
        };
    }

    // ── Type selection ────────────────────────────────────────────────────────

    private function promptType(): string
    {
        if (!defined('IS_DEVMODE') || !IS_DEVMODE) {
            return 'module';
        }
        return select(
            label:   'What to scaffold?',
            options: ['module' => 'Module', 'aptitude' => 'Aptitude (dev only)'],
            default: 'module',
        );
    }

    // ── Module flow ───────────────────────────────────────────────────────────

    private function moduleParams(): ?ScaffoldParams
    {
        $registry = select(
            label:   'Registry',
            options: Registries::cliOptions(),
            default: 'webkernelphp.com',
        );
        if ($registry === 'custom') {
            $registry = text(label: 'Registry hostname', required: true, validate: static fn ($v) => preg_match('/^[a-z0-9.\-]+$/', $v) ? null : 'Lowercase, dots, hyphens only.');
        }

        $vendor  = text(label: 'Vendor slug', required: true, validate: static fn ($v) => preg_match('/^[a-z0-9\-]+$/', $v) ? null : 'Lowercase, hyphens only.');
        $slug    = text(label: 'Module slug', required: true, validate: static fn ($v) => preg_match('/^[a-z0-9\-]+$/', $v) ? null : 'Lowercase, hyphens only.');
        $label   = text(label: 'Human-readable name', default: str($slug)->replace('-', ' ')->title()->toString(), required: true);
        $desc    = text(label: 'Short description', required: true);
        $version = text(label: 'Version', default: '0.1.0', required: true, validate: static fn ($v) => preg_match('/^\d+\.\d+\.\d+$/', $v) ? null : 'Use semver.');
        $party   = select(label: 'Party', options: ['first' => 'First — Numerimondes', 'second' => 'Second — your org', 'third' => 'Third — community'], default: 'second');
        $authorName  = text(label: 'Author name', required: true);
        $authorEmail = text(label: 'Author email', required: true, validate: static fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Invalid email.');
        $authorUrl   = text(label: 'Author URL (optional)');
        $phpVersion  = select(label: 'Min PHP', options: ['8.2', '8.3', '8.4'], default: '8.4');
        $lvVersion   = select(label: 'Min Laravel', options: ['11.0', '12.0'], default: '12.0');
        $license     = select(label: 'License', options: ['proprietary', 'MIT', 'EPL-2.0'], default: 'proprietary');

        $presetKey   = select(label: 'Structure preset', options: array_map(static fn ($p) => $p['label'], ArtifactMatrix::presets()), default: 'classic');
        $preset      = ArtifactMatrix::preset($presetKey);
        [$slots, $extraDirs] = $presetKey === 'custom'
            ? $this->collectCustomSlots()
            : [$preset['slots'], $preset['extraDirs']];

        $namespace  = NamingHelper::defaultNamespace('module', $slug, $vendor);
        $id         = NamingHelper::buildId('module', $registry, $vendor, $slug);
        $targetPath = base_path("modules/{$registry}/{$vendor}/{$slug}");

        note("ID: {$id} | Namespace: {$namespace} | Path: {$targetPath}");
        if (!confirm(label: 'Generate?', default: true)) {
            warning('Cancelled.');
            return null;
        }

        return new ScaffoldParams(
            type: 'module', targetPath: $targetPath,
            slug: $slug, pascal: NamingHelper::toPascal($slug),
            folderName: NamingHelper::toPascal($slug),
            namespace: $namespace, id: $id,
            label: $label, description: $desc,
            version: $version, phpVersion: $phpVersion, laravelVersion: $lvVersion,
            enabledSlots: $slots, extraDirs: $extraDirs,
            vendor: $vendor, registry: $registry, party: $party,
            authorName: $authorName, authorEmail: $authorEmail, authorUrl: $authorUrl ?? '',
            license: $license, presetKey: $presetKey, presetLabel: $preset['label'],
        );
    }

    // ── Aptitude flow ─────────────────────────────────────────────────────────

    private function aptitudeParams(): ?ScaffoldParams
    {
        $slug = text(label: 'Aptitude slug', required: true, validate: static fn ($v) => preg_match('/^[a-z0-9\-]+$/', $v) ? null : 'Lowercase, hyphens only.');

        $aptitudesRoot = defined('WEBKERNEL_APTITUDES_ROOT') ? WEBKERNEL_APTITUDES_ROOT : base_path('bootstrap/webkernel/src/Aptitudes');
        $folderName    = NamingHelper::folderFromSlug($slug);
        $targetPath    = $aptitudesRoot . '/' . $folderName;

        if (is_dir($targetPath)) {
            error("Aptitude [{$slug}] already exists at {$targetPath}");
            return null;
        }

        $label   = text(label: 'Human-readable name', default: str($slug)->replace('-', ' ')->title()->toString(), required: true);
        $desc    = text(label: 'Short description', required: true);
        $version = text(label: 'Version', default: '1.0.0', required: true, validate: static fn ($v) => preg_match('/^\d+\.\d+\.\d+$/', $v) ? null : 'Use semver.');
        $phpVersion = select(label: 'Min PHP', options: ['8.2', '8.3', '8.4'], default: '8.4');
        $lvVersion  = select(label: 'Min Laravel', options: ['11.0', '12.0'], default: '12.0');

        $slots = $this->promptSlots(['helpers', 'views', 'lang', 'config', 'migrations', 'console', 'blaze']);

        $namespace = NamingHelper::defaultNamespace('platform', $slug);
        $id        = NamingHelper::buildId('platform', defined('WEBKERNEL_PLATFORM_DEFAULT_SCOPE') ? WEBKERNEL_PLATFORM_DEFAULT_SCOPE : 'platform', $slug);

        note("ID: {$id} | Namespace: {$namespace} | Path: {$targetPath}");
        if (!confirm(label: 'Generate?', default: true)) {
            warning('Cancelled.');
            return null;
        }

        return new ScaffoldParams(
            type: 'aptitude', targetPath: $targetPath,
            slug: $slug, pascal: NamingHelper::toPascal($slug),
            folderName: $folderName, namespace: $namespace, id: $id,
            label: $label, description: $desc,
            version: $version, phpVersion: $phpVersion, laravelVersion: $lvVersion,
            enabledSlots: $slots, extraDirs: [],
        );
    }

    // ── Runner ────────────────────────────────────────────────────────────────

    private function runScaffold(?ScaffoldParams $params): int
    {
        if ($params === null) {
            return self::FAILURE;
        }
        try {
            $result = (new Scaffolder())->scaffold($params);
        } catch (Throwable $e) {
            error('Scaffolding failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->printResult($result);

        $dest = $params->isAptitude()
            ? "bootstrap/webkernel/src/Aptitudes/{$params->folderName}"
            : "modules/{$params->registry}/{$params->vendor}/{$params->slug}";

        outro("{$params->label} created at {$dest}");
        return self::SUCCESS;
    }

    private function printResult(ScaffoldResult $result): void
    {
        note("Created {$result->dirCount()} directories, {$result->fileCount()} files:");
        foreach ($result->dirs()  as $d) { $this->line('  <fg=blue>DIR</>  ' . $this->rel($d)); }
        foreach ($result->files() as $f) { $this->line('  <fg=green>FILE</> ' . $this->rel($f)); }
    }

    private function rel(string $path): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : base_path();
        return ltrim(str_replace($base, '', $path), '/\\');
    }

    // ── Slot helpers ──────────────────────────────────────────────────────────

    private function promptSlots(array $candidates): array
    {
        $slots   = ArtifactMatrix::slots();
        $enabled = [];
        foreach ($candidates as $name) {
            if (confirm(label: $slots[$name]['label'] ?? $name, default: $name === 'helpers')) {
                $enabled[] = $name;
            }
        }
        return $enabled;
    }

    private function collectCustomSlots(): array
    {
        $extraDirs = [];
        note('Extra directories (empty to stop):');
        while (true) {
            $dir = text(label: 'Directory', required: false);
            if ($dir === '') {
                break;
            }
            $extraDirs[] = ltrim($dir, '/');
        }
        $slots = $this->promptSlots(array_keys(ArtifactMatrix::slots()));
        return [$slots, $extraDirs];
    }
}
