<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Webkernel\CP\System\Presentation\Resources\DependencyManager\Services\NpmService;
use Webkernel\Traits\HasBackgroundTasks;

class UpdateAllNpmPackagesAction extends Action
{
    use HasBackgroundTasks;
    public static function getDefaultName(): ?string
    {
        return 'update_all_npm_packages';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Update All Packages')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->form([
                Radio::make('mode')
                    ->label('How would you like to run this?')
                    ->options([
                        'sync' => 'Run now (browser waits — may take several minutes)',
                        'background' => 'Run in background (recommended)',
                    ])
                    ->default('background')
                    ->required(),
            ])
            ->modalHeading('Update All NPM Packages')
            ->modalDescription('This will update all outdated packages to their latest versions.')
            ->modalSubmitActionLabel('Update All')
            ->action(function (array $data) {
                try {
                    if ($data['mode'] === 'background') {
                        $this->updateAllPackagesInBackground();
                    } else {
                        $this->updateAllPackagesSync();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Bulk Update Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function updateAllPackagesInBackground(): void
    {
        $task = $this->createBackgroundTask(
            'npm_update_all',
            'Update all NPM packages'
        );

        $this->dispatchAllNpmPackagesUpdate((string) $task->id);

        Notification::make()
            ->title('Background Task Created')
            ->body('Updating all packages. Check Background Tasks for status.')
            ->success()
            ->send();
    }

    private function updateAllPackagesSync(): void
    {
        $service = app(NpmService::class);
        $npmClient = config('dependency-manager.npm_client', 'npm');

        try {
            $command = match ($npmClient) {
                'yarn' => ['yarn', 'upgrade'],
                'pnpm' => ['pnpm', 'update'],
                default => ['npm', 'update'],
            };

            $process = new Process(
                $command,
                base_path(),
                ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME') ?: '/root']
            );

            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput() ?: 'Update failed');
            }

            $service->clearCache();
            \Illuminate\Support\Facades\Cache::forget('filament-dependency-manager:npm-all');

            Notification::make()
                ->title('All NPM Packages Updated')
                ->body('All dependencies have been updated successfully.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            throw new \Exception("Failed to update packages: " . $e->getMessage());
        }
    }
}
