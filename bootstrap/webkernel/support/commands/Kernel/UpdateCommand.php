<?php declare(strict_types=1);

namespace Webkernel\Commands\Kernel;

use Illuminate\Console\Command;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\BackOffice\System\Domain\Updates\WebkernelUpdater;

use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

/**
 * Update the Webkernel core (bootstrap/webkernel) to a new version.
 *
 * CLI:
 *   php artisan webkernel:kernel:update {version?}
 *     --no-backup    Skip backup of the current kernel directory
 *     --keep=        Comma-separated list of dirs to preserve (default: var-elements)
 *     --token=       GitHub token for private / rate-limited access
 *     --pre-release  Include pre-release versions
 *
 * UI: call Updater::kernel()->toVersion(...)->execute() from Filament pages.
 */
final class UpdateCommand extends Command
{
    protected $signature = 'webkernel:kernel:update
        {version?       : Target version/tag (default: latest stable) }
        {--no-backup    : Skip backup of the current kernel directory }
        {--keep=        : Comma-separated dirs to preserve across update (default: var-elements) }
        {--token=       : GitHub bearer token }
        {--pre-release  : Include pre-release versions }';

    protected $description = 'Update the Webkernel core to the latest or a specific version.';

    public function handle(): int
    {
        $version    = $this->argument('version') ?: null;
        $token      = $this->option('token')     ?: null;
        $backup     = !$this->option('no-backup');
        $preRelease = (bool) $this->option('pre-release');
        $keepRaw    = $this->option('keep');
        $keepDirs   = $keepRaw ? explode(',', $keepRaw) : ['var-elements'];

        // ── Interactive version selection ─────────────────────────────────────
        if ($version === null && !$this->option('no-interaction')) {
            $version = text(
                label:       'Target version',
                placeholder: 'latest',
                default:     '',
            ) ?: null;
        }

        warning('This will replace bootstrap/webkernel with the new version.');
        note("Version : " . ($version ?? 'latest'));
        note("Backup  : " . ($backup   ? 'yes' : 'no'));
        note("Preserve: " . implode(', ', $keepDirs));

        if (!$this->option('no-interaction')) {
            if (!confirm('Proceed with kernel update?', default: false)) {
                info('Aborted.');
                return self::SUCCESS;
            }
        }

        // ── Execute ───────────────────────────────────────────────────────────
        try {
            $updater = WebkernelUpdater::webkernel()
                ->withBackup($backup)
                ->keepDirs($keepDirs)
                ->includePreReleases($preRelease);

            if ($version !== null) {
                $updater = $updater->toVersion($version);
            }

            if ($token !== null) {
                $updater = $updater->withToken($token);
            }

            $path = spin(
                fn () => $updater->execute(),
                'Updating kernel…',
            );

            $this->components->info("Kernel updated at: {$path}");

            return self::SUCCESS;
        } catch (NetworkException $e) {
            error($e->getMessage());
            return self::FAILURE;
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }
    }
}
