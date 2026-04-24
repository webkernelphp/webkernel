<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;
use Webkernel\Traits\HasBackgroundTasks;

class UpdateAllComposerPackagesAction extends Action
{
    use HasBackgroundTasks;
    public static function getDefaultName(): ?string
    {
        return 'update_all_composer_packages';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Update All Packages')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->schema([
                Radio::make('mode')
                    ->label('How would you like to run this?')
                    ->options([
                        'sync' => 'Run now (browser waits — may take several minutes)',
                        'background' => 'Run in background (recommended)',
                    ])
                    ->default('background')
                    ->required(),
            ])
            ->modalHeading('Update All Composer Packages')
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
            'composer_update_all',
            'Update all Composer packages'
        );

        $this->dispatchAllComposerPackagesUpdate((string) $task->id);

        Notification::make()
            ->title('Background Task Created')
            ->body('Updating all packages. Check Background Tasks for status.')
            ->success()
            ->send();
    }

    private function updateAllPackagesSync(): void
    {
        $service = app(ComposerService::class);
        $composerBinary = config('dependency-manager.composer_binary', 'composer');

        try {
            $command = str_contains($composerBinary, ' ')
                ? array_merge(explode(' ', $composerBinary), ['update', '--no-interaction'])
                : [$composerBinary, 'update', '--no-interaction'];

            $process = new Process(
                $command,
                base_path(),
                [
                    'PATH' => dirname(PHP_BINARY) . ':/usr/local/bin:/usr/bin:/bin',
                    'HOME' => getenv('HOME') ?: '/root',
                    'COMPOSER_HOME' => getenv('HOME') . '/.composer',
                ]
            );

            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput() ?: 'Update failed');
            }

            $service->clearCache();
            \Illuminate\Support\Facades\Cache::forget('filament-dependency-manager:composer-all');

            Notification::make()
                ->title('All Composer Packages Updated')
                ->body('All dependencies have been updated successfully.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            throw new \Exception("Failed to update packages: " . $e->getMessage());
        }
    }
}
