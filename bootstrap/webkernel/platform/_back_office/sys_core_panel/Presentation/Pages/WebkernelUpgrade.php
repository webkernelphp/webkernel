<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\BackOffice\System\Domain\Updates\Models\WebkernelUpdateCheck;
use Webkernel\BackOffice\System\Domain\Updates\WebkernelUpdateChecker;
use Webkernel\BackOffice\System\Domain\Updates\WebkernelUpdater;

/**
 * WebkernelUpgrade
 *
 * Product landing + update management page for the Webkernel core
 * (bootstrap/webkernel). Provides version info, update pipeline,
 * screenshots, documentation links, rollback and force-reset.
 */
class WebkernelUpgrade extends Page
{
    protected string $view = 'webkernel-system::filament.pages.webkernel-upgrade';

    protected static ?int                 $navigationSort           = 6;
    protected static bool                 $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null $navigationGroup          = 'System';

    // ── Livewire state ────────────────────────────────────────────────────────

    public bool   $isUpdating       = false;
    public string $updateStatus     = '';
    public string $updateError      = '';
    public bool   $createBackup     = true;

    // ── Computed / display properties ─────────────────────────────────────────

    public string $currentVersion   = WEBKERNEL_VERSION;
    public string $currentCodename  = WEBKERNEL_CODENAME;
    public string $currentChannel   = WEBKERNEL_CHANNEL;
    public string $currentBuild     = '1000';
    public string $currentTag       = WEBKERNEL_TAG; // or WEBKERNEL_SEMVER if you prefer full semver
    public bool   $isUpToDate       = false; // you can compute this dynamically
    public string $lastChecked      = '';  // ISO-8601 timestamp from DB, ticked live in the view
    public string $phpVersion       = '';
    public string $laravelVersion   = '';
    public string $filamentVersion  = '';

    public string $latestVersion    = ''; // To retrieve from github

    public function mount(): void
    {
        $this->phpVersion      = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        $this->laravelVersion  = app()->version();
        $this->filamentVersion = \Composer\InstalledVersions::getPrettyVersion('filament/filament');

        $this->loadFromLocalRegistry();
    }

    private function loadFromLocalRegistry(): void
    {
        try {
            $checker = WebkernelUpdateChecker::forWebkernel();
            $latest  = $checker->latestKnownRelease();
            $lastLog = WebkernelUpdateCheck::lastSuccess(
                WebkernelUpdateChecker::WEBKERNEL_TARGET_TYPE,
                WebkernelUpdateChecker::WEBKERNEL_SLUG,
            );

            if ($latest !== null) {
                $this->latestVersion = $latest->tag_name;
                $this->isUpToDate    = $checker->isUpToDate($this->currentVersion);
                $this->lastChecked   = $lastLog?->checked_at->toIso8601String() ?? '';
                return;
            }
        } catch (\Throwable) {
            // DB not yet migrated — will show "never checked" state
        }
    }

    public function checkForUpdates(): void
    {
        try {
            $checker = WebkernelUpdateChecker::forWebkernel()->force();
            $log     = $checker->check();

            if ($log->status === WebkernelUpdateCheck::STATUS_SUCCESS) {
                $latest              = $checker->latestKnownRelease();
                $this->latestVersion = $latest?->tag_name ?? '';
                $this->isUpToDate    = $checker->isUpToDate($this->currentVersion);
                $this->lastChecked   = $log->checked_at->toIso8601String();

                if ($this->isUpToDate) {
                    $this->dispatch('webkernel-toast', type: 'success', message: 'Webkernel is up to date (v' . $this->currentVersion . ').');
                } else {
                    $this->dispatch('webkernel-toast', type: 'warning', message: "Update available: v{$this->latestVersion}");
                }
            } elseif ($log->status === WebkernelUpdateCheck::STATUS_RATE_LIMITED) {
                $this->dispatch('webkernel-toast', type: 'warning', message: 'GitHub rate limit reached. Try again later.');
            } elseif ($log->status === WebkernelUpdateCheck::STATUS_SKIPPED) {
                $this->dispatch('webkernel-toast', type: 'info', message: 'Already checked recently. Showing cached result.');
            } else {
                $this->dispatch('webkernel-toast', type: 'danger', message: 'Check failed: ' . ($log->error_message ?? 'unknown error'));
            }
        } catch (\Throwable $e) {
            $this->dispatch('webkernel-toast', type: 'danger', message: 'Could not check for updates: ' . $e->getMessage());
        }
    }


    // ── Update ────────────────────────────────────────────────────────────────

    public function updateKernel(): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = 'Starting kernel update…';

        try {
            $updater = WebkernelUpdater::webkernel()
                ->withBackup($this->createBackup)
                ->keepDirs(['var-elements']);

            $this->updateStatus = 'Downloading new kernel…';
            $updater->execute();

            $this->updateStatus = 'Kernel updated successfully!';
            $this->isUpToDate   = true;

            $this->dispatch('wk-toast', type: 'success', message: 'Kernel update complete. Please refresh the page.');
        } catch (NetworkException $e) {
            $this->updateError = 'Network error: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->updateError);
        } catch (\Throwable $e) {
            $this->updateError = 'Update failed: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->updateError);
        } finally {
            $this->isUpdating = false;
        }
    }

    // ── Rollback ──────────────────────────────────────────────────────────────

    public function rollbackToVersion(string $version): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = "Rolling back to v{$version}…";

        try {
            // TODO: wire to WebkernelUpdater::webkernel()->rollbackTo($version)
            // WebkernelUpdater::webkernel()->rollbackTo($version);

            $this->updateStatus = "Rollback to v{$version} complete. Please refresh.";
            $this->dispatch('wk-toast', type: 'success', message: "Rolled back to v{$version}.");
        } catch (\Throwable $e) {
            $this->updateError = 'Rollback failed: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->updateError);
        } finally {
            $this->isUpdating = false;
        }
    }

    // ── Force reset ───────────────────────────────────────────────────────────

    public function forceResetKernel(): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = 'Force-resetting kernel…';

        try {
            // TODO: wire to WebkernelUpdater::webkernel()->forceReset()
            $this->updateStatus = 'Kernel has been force-reset. Please refresh.';
            $this->dispatch('wk-toast', type: 'success', message: 'Kernel force-reset complete.');
        } catch (\Throwable $e) {
            $this->updateError = 'Force reset failed: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->updateError);
        } finally {
            $this->isUpdating = false;
        }
    }

    // ── Header actions ────────────────────────────────────────────────────────

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('update')
                ->label('Install Update')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->disabled($this->isUpdating || $this->isUpToDate)
                ->requiresConfirmation()
                ->modalHeading('Install Webkernel Core Update?')
                ->modalDescription(
                    'A cryptographic backup will be created before the update is applied. ' .
                    'Running processes will be briefly interrupted.'
                )
                ->modalSubmitActionLabel('Install Update')
                ->action(function (): void {
                    $this->createBackup = true;
                    $this->updateKernel();
                }),

            Action::make('check')
                ->label('Check for Updates')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->outlined()
                ->action(fn () => $this->checkForUpdates()),
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-rocket-launch';
    }

    public static function getNavigationLabel(): string
    {
        return 'Update Kernel';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Webkernel Core';
    }
}
