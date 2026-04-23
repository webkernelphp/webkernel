<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use UnitEnum;
use Webkernel\BackOffice\System\Models\WebkernelRelease;
use Webkernel\BackOffice\System\Models\WebkernelUpdateCheck;

class WebkernelUpgrade extends Page
{
    protected string $view = 'webkernel-system::filament.pages.webkernel-upgrade';

    protected static ?int                 $navigationSort           = 6;
    protected static bool                 $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null $navigationGroup          = 'System';

    public bool   $isUpdating       = false;
    public string $updateStatus     = '';
    public string $updateError      = '';
    public bool   $createBackup     = true;

    public string $currentVersion   = WEBKERNEL_VERSION;
    public string $currentCodename  = WEBKERNEL_CODENAME;
    public string $currentChannel   = WEBKERNEL_CHANNEL;
    public string $phpVersion       = '';
    public string $laravelVersion   = '';
    public string $filamentVersion  = '';
    public string $latestVersion    = '';
    public bool   $isUpToDate       = false;
    public string $lastChecked      = '';

    /** @var array<int, array{version: string, codename: string, date: string, notes: string, current: bool}> */
    public array $releases          = [];

    /** @var array<int, array{icon: string, title: string, body: string}> */
    public array $features          = [];

    /** @var array<int, array{icon: string, label: string, url: string}> */
    public array $docLinks          = [];

    public string $videoId          = '';

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
            $latest = WebkernelRelease::forTarget('webkernel', 'foundation')
                ->stable()
                ->orderByDesc('version')
                ->first();

            if ($latest !== null) {
                $this->latestVersion = $latest->version;
                $this->isUpToDate    = version_compare($this->currentVersion, $latest->version, '>=');
                $this->lastChecked   = $latest->updated_at->toIso8601String();
            }

            $rows = WebkernelRelease::forTarget('webkernel', 'foundation')
                ->stable()
                ->orderByDesc('version')
                ->limit(20)
                ->get();

            $this->releases = $rows->map(fn($r) => [
                'version'  => $r->version,
                'codename' => $r->codename ?? '',
                'date'     => $r->published_at?->toDateString() ?? $r->created_at->toDateString(),
                'notes'    => $r->release_notes ?? '',
                'current'  => version_compare($r->version, $this->currentVersion, '='),
            ])->all();

        } catch (\Throwable) {
            // DB not yet migrated
        }
    }

    public function checkForUpdates(): void
    {
        try {
            $response = Http::timeout(10)->get('https://api.github.com/repos/webkernelphp/foundation/tags');

            $remaining = (int) ($response->header('X-RateLimit-Remaining') ?? 0);
            $reset = $response->header('X-RateLimit-Reset');

            if (!$response->successful()) {
                throw new \RuntimeException("GitHub API returned {$response->status()}");
            }

            $tags = $response->json() ?? [];
            if (empty($tags)) {
                throw new \RuntimeException('No tags found in repository');
            }

            $synced = WebkernelRelease::syncFromGitHubTags($tags, 'webkernel', 'foundation');

            WebkernelUpdateCheck::create([
                'id'                    => Str::ulid()->toBase32(),
                'target_type'           => 'webkernel',
                'target_slug'           => 'foundation',
                'registry'              => 'github',
                'status'                => 'success',
                'latest_tag_found'      => $tags[0]['name'] ?? null,
                'releases_synced'       => $synced,
                'rate_limit_remaining'  => $remaining,
                'rate_limit_reset_at'   => $reset ? now()->setTimestamp($reset) : null,
                'checked_at'            => now(),
            ]);

            if ($remaining < 10) {
                Notification::make()
                    ->title("GitHub rate limit low: {$remaining} requests remaining")
                    ->warning()
                    ->send();
            }

            $this->loadFromLocalRegistry();

            if ($this->isUpToDate) {
                Notification::make()
                    ->title("Webkernel is up to date (v{$this->currentVersion})")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title("Update available: v{$this->latestVersion}")
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            WebkernelUpdateCheck::create([
                'id'            => Str::ulid()->toBase32(),
                'target_type'   => 'webkernel',
                'target_slug'   => 'foundation',
                'registry'      => 'github',
                'status'        => 'error',
                'error_message' => $e->getMessage(),
                'checked_at'    => now(),
            ]);

            Notification::make()
                ->title('Could not check for updates: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updateKernel(): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = 'Starting kernel update…';

        try {
            $this->updateStatus = 'Finding latest release…';
            $latest = WebkernelRelease::forTarget('webkernel', 'foundation')
                ->stable()
                ->orderByDesc('version')
                ->first();

            if (!$latest) {
                throw new \RuntimeException('No releases available');
            }

            $this->updateStatus = 'Downloading release…';
            $downloadUrl = $latest->zipball_url ?? "https://github.com/webkernelphp/foundation/archive/refs/tags/{$latest->tag_name}.zip";

            $result = webkernel()->do()
                ->from($downloadUrl)
                ->backup(path: WEBKERNEL_PATH, except: ['var-elements', 'var-logs'])
                ->extract()
                ->swap()
                ->run();

            if (!$result->success) {
                $result->rollback();
                throw new \RuntimeException($result->error ?? 'Update failed');
            }

            $this->updateStatus = 'Kernel updated successfully!';
            $this->isUpToDate   = true;
            $this->currentVersion = $latest->version;

            Notification::make()
                ->title('Kernel update complete. Please refresh the page.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->updateError = $e->getMessage();
            Notification::make()
                ->title('Update failed: ' . $this->updateError)
                ->danger()
                ->send();
        } finally {
            $this->isUpdating = false;
        }
    }

    public function rollbackToVersion(string $version): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = "Rolling back to v{$version}…";

        try {
            $release = WebkernelRelease::forTarget('webkernel', 'foundation')
                ->where('version', $version)
                ->first();

            if (!$release) {
                throw new \RuntimeException("Release not found: v{$version}");
            }

            $this->updateStatus = 'Downloading release…';
            $downloadUrl = $release->zipball_url ?? "https://github.com/webkernelphp/foundation/archive/refs/tags/{$release->tag_name}.zip";

            $result = webkernel()->do()
                ->from($downloadUrl)
                ->backup(path: WEBKERNEL_PATH, except: ['var-elements', 'var-logs'])
                ->extract()
                ->swap()
                ->run();

            if (!$result->success) {
                $result->rollback();
                throw new \RuntimeException($result->error ?? 'Rollback failed');
            }

            $this->updateStatus = "Rollback to v{$version} complete. Please refresh.";
            $this->currentVersion = $version;

            Notification::make()
                ->title("Rolled back to v{$version}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->updateError = $e->getMessage();
            Notification::make()
                ->title('Rollback failed: ' . $this->updateError)
                ->danger()
                ->send();
        } finally {
            $this->isUpdating = false;
        }
    }

    public function forceResetKernel(): void
    {
        $this->isUpdating  = true;
        $this->updateError = '';
        $this->updateStatus = 'Force-resetting kernel…';

        try {
            $release = WebkernelRelease::forTarget('webkernel', 'foundation')
                ->where('version', $this->currentVersion)
                ->first();

            if (!$release) {
                throw new \RuntimeException("Release not found: v{$this->currentVersion}");
            }

            $this->updateStatus = 'Downloading release…';
            $downloadUrl = $release->zipball_url ?? "https://github.com/webkernelphp/foundation/archive/refs/tags/{$release->tag_name}.zip";

            $result = webkernel()->do()
                ->from($downloadUrl)
                ->backup(path: WEBKERNEL_PATH, except: ['var-elements', 'var-logs'])
                ->extract()
                ->swap()
                ->run();

            if (!$result->success) {
                $result->rollback();
                throw new \RuntimeException($result->error ?? 'Force reset failed');
            }

            $this->updateStatus = 'Kernel has been force-reset. Please refresh.';

            Notification::make()
                ->title('Kernel force-reset complete.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->updateError = $e->getMessage();
            Notification::make()
                ->title('Force reset failed: ' . $this->updateError)
                ->danger()
                ->send();
        } finally {
            $this->isUpdating = false;
        }
    }

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
                ->action(fn() => $this->updateKernel()),

            Action::make('check')
                ->label('Check for Updates')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->outlined()
                ->action(fn() => $this->checkForUpdates()),
        ];
    }

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
