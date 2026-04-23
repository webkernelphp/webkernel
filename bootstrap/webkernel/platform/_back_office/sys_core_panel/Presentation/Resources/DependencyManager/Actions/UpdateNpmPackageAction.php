<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Symfony\Component\Process\Process;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models\NpmPackage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\NpmService;

class UpdateNpmPackageAction extends Action
{
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
            ->requiresConfirmation()
            ->modalHeading('Update Package')
            ->modalDescription('This will update the package to the latest version. This may take a few moments.')
            ->modalSubmitActionLabel('Update')
            ->action(function (NpmPackage $record) {
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

    private function updatePackage(NpmPackage $record): void
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
