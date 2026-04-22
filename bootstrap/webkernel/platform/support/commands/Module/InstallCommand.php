<?php declare(strict_types=1);

namespace Webkernel\Commands\Module;

use Illuminate\Console\Command;
use Webkernel\Integration\ModuleInstaller;
use Webkernel\Registry\Providers;
use Webkernel\Integration\Git\Exceptions\NetworkException;

use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\note;

/**
 * Install a Webkernel module from any supported registry.
 *
 * CLI:
 *   php artisan webkernel:module:install {id?}
 *     --version=     Specific version/tag (default: latest)
 *     --no-backup    Skip backup of existing installation
 *     --no-hooks     Skip pre/post install hooks
 *     --dry-run      Resolve and validate but do not write anything
 *
 * UI: call Installer::module(...)->execute() directly from Filament pages.
 *
 * Examples:
 *   php artisan webkernel:module:install github-com::emyassine/erp --version=1.2.0
 *   php artisan webkernel:module:install  (interactive prompts)
 */
final class InstallCommand extends Command
{
    protected $signature = 'webkernel:module:install
        {id?            : Module ID — registry-slug::vendor/slug }
        {--version=     : Version tag to install (default: latest) }
        {--no-backup    : Skip backup of existing installation }
        {--no-hooks     : Skip pre/post install hooks }
        {--dry-run      : Resolve but do not write }
        {--token=       : Bearer token for private registries }';

    protected $description = 'Install a Webkernel module from GitHub, GitLab, Numerimondes, or any supported registry.';

    public function handle(): int
    {
        $id = $this->argument('id');

        // ── Interactive mode ──────────────────────────────────────────────────
        if ($id === null) {
            $id = $this->promptForId();
        }

        $version  = $this->option('version')  ?: null;
        $token    = $this->option('token')     ?: null;
        $backup   = !$this->option('no-backup');
        $hooks    = !$this->option('no-hooks');
        $dryRun   = (bool) $this->option('dry-run');

        if ($version === null && !$this->option('no-interaction')) {
            $version = text(
                label:       'Version to install',
                placeholder: 'latest',
                default:     '',
            ) ?: null;
        }

        note("Module  : {$id}");
        note("Version : " . ($version ?? 'latest'));
        note("Backup  : " . ($backup  ? 'yes' : 'no'));
        note("Dry-run : " . ($dryRun  ? 'yes' : 'no'));

        if (!$this->option('no-interaction')) {
            $confirmed = confirm('Proceed with installation?', default: true);
            if (!$confirmed) {
                info('Aborted.');
                return self::SUCCESS;
            }
        }

        // ── Execute ───────────────────────────────────────────────────────────
        try {
            $installer = ModuleInstaller::fromId($id)
                ->withBackup($backup)
                ->withHooks($hooks);

            if ($version !== null) {
                $installer = $installer->toVersion($version);
            }

            if ($token !== null) {
                $installer = $installer->withToken($token);
            }

            if ($dryRun) {
                info('[dry-run] Resolved — nothing written.');
                return self::SUCCESS;
            }

            $path = spin(
                fn () => $installer->execute(),
                'Installing…',
            );

            $this->components->info("Installed at: {$path}");

            return self::SUCCESS;
        } catch (NetworkException $e) {
            error($e->getMessage());
            return self::FAILURE;
        } catch (\Throwable $e) {
            error($e->getMessage());
            $this->components->twoColumnDetail('Trace', $e->getTraceAsString());
            return self::FAILURE;
        }
    }

    private function promptForId(): string
    {
        $registry = select(
            label:   'Registry',
            options: [
                'github-com'           => 'GitHub (github.com)',
                'gitlab-com'           => 'GitLab (gitlab.com)',
                'webkernelphp-com'     => 'Webkernel Registry (webkernelphp.com)',
                'git-numerimondes-com' => 'Numerimondes (git.numerimondes.com)',
                'other'                => 'Other (enter manually)',
            ],
        );

        if ($registry === 'other') {
            $registry = text(label: 'Registry slug (e.g. my-host-com)', required: true);
        }

        $vendor = text(label: 'Vendor / owner', placeholder: 'emyassine', required: true);
        $slug   = text(label: 'Module slug',    placeholder: 'my-module',  required: true);

        return "{$registry}::{$vendor}/{$slug}";
    }
}
