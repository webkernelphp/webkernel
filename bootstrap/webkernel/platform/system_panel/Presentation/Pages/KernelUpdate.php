<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkernel\Integration\KernelUpdater;
use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * KernelUpdate
 *
 * Update the Webkernel core (bootstrap/webkernel) to a new version.
 * Simple button with confirmation modal.
 */
class KernelUpdate extends Page
{
    protected string $view = 'webkernel-system::filament.pages.kernel-update';

    protected static ?int                       $navigationSort           = 6;
    protected static bool                       $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null       $navigationGroup          = 'System';

    // ── Livewire state ────────────────────────────────────────────────────────

    public bool   $isUpdating = false;
    public string $updateStatus = '';
    public string $updateError = '';
    public bool   $createBackup = true;

    // ── Livewire actions ──────────────────────────────────────────────────────

    public function updateKernel(): void
    {
        $this->isUpdating = true;
        $this->updateError = '';
        $this->updateStatus = 'Starting kernel update...';

        try {
            $updater = KernelUpdater::kernel()
                ->withBackup($this->createBackup)
                ->keepDirs(['var-elements']);

            $this->updateStatus = 'Downloading new kernel...';

            $path = $updater->execute();

            $this->updateStatus = 'Kernel updated successfully!';
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

    // ── Header actions ────────────────────────────────────────────────────────

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('update')
                ->label('Update Webkernel Core')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->disabled($this->isUpdating)
                ->requiresConfirmation()
                ->modalHeading('Update Webkernel Core?')
                ->modalDescription('All running processes will be interrupted. A backup is recommended.')
                ->modalSubmitActionLabel('Update')
                ->action(function () {
                    $this->createBackup = true;
                    $this->updateKernel();
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
        return 'Update Webkernel Core';
    }
}
