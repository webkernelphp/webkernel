<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
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
            ->requiresConfirmation()
            ->modalHeading('Update Package')
            ->modalDescription('This will update the package to the latest version. This may take a few moments.')
            ->modalSubmitActionLabel('Update')
            ->action(function (ComposerPackage $record) {
                try {
                    $this->updatePackage($record);
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Update Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function updatePackage(ComposerPackage $record): void
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
