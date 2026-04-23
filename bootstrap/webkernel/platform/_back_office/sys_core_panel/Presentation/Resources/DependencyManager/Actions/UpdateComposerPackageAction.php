<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Jobs\UpdateComposerPackageJob;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models\ComposerPackage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;

class UpdateComposerPackageAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'update_composer_package';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Update Package')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('success')
            ->form([
                Radio::make('mode')
                    ->label('How would you like to run this?')
                    ->options([
                        'sync' => 'Run now (browser waits — may take minutes)',
                        'background' => 'Run in background (recommended)',
                    ])
                    ->default('background')
                    ->required(),
            ])
            ->modalHeading('Update Package')
            ->modalDescription('This will update the package to the latest version.')
            ->modalSubmitActionLabel('Update')
            ->action(function (ComposerPackage $record, array $data) {
                try {
                    if ($data['mode'] === 'background') {
                        $this->updatePackageInBackground($record);
                    } else {
                        $this->updatePackageSync($record);
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Update Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function updatePackageInBackground(ComposerPackage $record): void
    {
        $task = WebkernelBackgroundTask::create([
            'type' => 'composer_update',
            'label' => "Update {$record->name} to {$record->latest}",
            'payload' => [
                'package' => $record->name,
                'version' => $record->latest,
            ],
            'status' => 'pending',
        ]);

        dispatch(new UpdateComposerPackageJob($task->id, $record->name, $record->latest));

        Notification::make()
            ->title('Background Task Created')
            ->body("Updating {$record->name} to {$record->latest}. Check Background Tasks for status.")
            ->success()
            ->send();
    }

    private function updatePackageSync(ComposerPackage $record): void
    {
        $service = app(ComposerService::class);

        try {
            $composerBinary = config('dependency-manager.composer_binary', 'composer');

            $command = str_contains($composerBinary, ' ')
                ? array_merge(explode(' ', $composerBinary), ['require', "{$record->name}:{$record->latest}", '--no-interaction', '--no-dev'])
                : [$composerBinary, 'require', "{$record->name}:{$record->latest}", '--no-interaction', '--no-dev'];

            $process = new Process(
                $command,
                base_path(),
                [
                    'PATH' => dirname(PHP_BINARY) . ':/usr/local/bin:/usr/bin:/bin',
                    'HOME' => getenv('HOME') ?: '/root',
                    'COMPOSER_HOME' => getenv('HOME') . '/.composer',
                ]
            );

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput() ?: 'Update failed');
            }

            $service->clearCache();
            \Illuminate\Support\Facades\Cache::forget('filament-dependency-manager:composer-all');

            Notification::make()
                ->title('Package Updated Successfully')
                ->body("{$record->name} has been updated to {$record->latest}")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            throw new \Exception("Failed to update {$record->name}: " . $e->getMessage());
        }
    }
}
