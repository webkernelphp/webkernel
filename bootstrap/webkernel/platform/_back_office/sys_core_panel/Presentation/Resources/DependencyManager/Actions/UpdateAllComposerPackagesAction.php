<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;

class UpdateAllComposerPackagesAction extends Action
{
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
            ->requiresConfirmation()
            ->modalHeading('Update All Composer Packages')
            ->modalDescription('This will update all outdated packages to their latest versions. This may take several minutes.')
            ->modalSubmitActionLabel('Update All')
            ->action(function () {
                try {
                    $this->updateAllPackages();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Bulk Update Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function updateAllPackages(): void
    {
        $service = app(ComposerService::class);
        $composerBinary = config('dependency-manager.composer_binary', 'composer');

        try {
            $command = str_contains($composerBinary, ' ')
                ? array_merge(explode(' ', $composerBinary), ['update', '--no-interaction', '--no-dev'])
                : [$composerBinary, 'update', '--no-interaction', '--no-dev'];

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
