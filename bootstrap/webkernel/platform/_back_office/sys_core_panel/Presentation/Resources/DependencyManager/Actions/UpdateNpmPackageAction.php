<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models\NpmPackage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\NpmService;
use Webkernel\Traits\HasBackgroundTasks;

class UpdateNpmPackageAction extends Action
{
    use HasBackgroundTasks;
    public static function getDefaultName(): ?string
    {
        return 'update_npm_package';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Update Package')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('success')
            ->visible(fn (NpmPackage $record): bool => (bool) $record->has_update)
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
            ->action(function (NpmPackage $record, array $data) {
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

    private function updatePackageInBackground(NpmPackage $record): void
    {
        $task = $this->createBackgroundTask(
            'npm_update',
            "Update {$record->name} to {$record->latest}",
            ['package' => $record->name, 'version' => $record->latest]
        );

        $this->dispatchNpmPackageUpdate((string) $task->id, $record->name, $record->latest);

        Notification::make()
            ->title('Background Task Created')
            ->body("Updating {$record->name} to {$record->latest}. Check Background Tasks for status.")
            ->success()
            ->send();
    }

    private function updatePackageSync(NpmPackage $record): void
    {
        $service = app(NpmService::class);
        $npmClient = config('dependency-manager.npm_client', 'npm');

        try {
            $command = match ($npmClient) {
                'yarn' => ['yarn', 'add', "{$record->name}@{$record->latest}"],
                'pnpm' => ['pnpm', 'add', "{$record->name}@{$record->latest}"],
                default => ['npm', 'install', "{$record->name}@{$record->latest}"],
            };

            $process = new Process(
                $command,
                base_path(),
                ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME') ?: '/root']
            );

            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput() ?: 'Update failed');
            }

            $service->clearCache();
            \Illuminate\Support\Facades\Cache::forget('filament-dependency-manager:npm-all');

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
