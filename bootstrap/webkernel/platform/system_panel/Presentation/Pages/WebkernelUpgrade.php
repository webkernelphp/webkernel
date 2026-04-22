<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Http;
use UnitEnum;
use Webkernel\Integration\KernelUpdater;
use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * WebkernelUpgrade
 *
 * Product landing + update management page for the Webkernel core
 * (bootstrap/webkernel). Provides version info, update pipeline,
 * screenshots, documentation links, rollback and force-reset.
 */
class WebkernelUpgrade extends Page
{
    protected string $view = 'webkernel-system::filament.pages.kernel-update';

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
    public string $lastChecked      = WEBKERNEL_RELEASED_AT;
    public string $phpVersion       = '';
    public string $laravelVersion   = '';
    public string $filamentVersion  = '';

    public string $latestVersion    = ''; // To retrieve from github

    public function mount(): void
    {
        $this->phpVersion      = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        $this->laravelVersion  = app()->version();
        $this->filamentVersion = \Composer\InstalledVersions::getPrettyVersion('filament/filament');

        $this->checkForUpdates();
    }

    public function checkForUpdates(): void
    {
        try {
            $this->latestVersion = $this->fetchLatestVersionFromGitHub();
            $this->lastChecked = now()->diffForHumans();
            $this->isUpToDate = version_compare($this->currentVersion, $this->latestVersion, '>=');
        } catch (\Throwable $e) {
            $this->dispatch('wk-toast', type: 'warning', message: 'Could not check for updates: ' . $e->getMessage());
        }
    }

    private function fetchLatestVersionFromGitHub(): string
    {
        $response = Http::timeout(5)->get('https://api.github.com/repos/webkernelphp/foundation/releases/latest');

        if (!$response->successful()) {
            throw new \Exception('GitHub API request failed');
        }

        $tag = $response->json('tag_name');
        if (!$tag) {
            throw new \Exception('No release tag found');
        }

        return ltrim($tag, 'v');
    }


    // ── Update ────────────────────────────────────────────────────────────────

    public function updateKernel(): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = 'Starting kernel update…';

        try {
            $updater = KernelUpdater::kernel()
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
            // TODO: wire to KernelUpdater::rollback($version)
            // KernelUpdater::kernel()->rollbackTo($version);

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
            // TODO: wire to KernelUpdater::forceReset()
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
                ->action(function (): void {
                    $this->checkForUpdates();
                    if ($this->isUpToDate) {
                        $this->dispatch('wk-toast', type: 'success', message: 'Kernel is up to date!');
                    } else {
                        $this->dispatch('wk-toast', type: 'warning', message: "Update available: v{$this->latestVersion}");
                    }
                }),
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
