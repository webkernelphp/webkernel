<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns;

use Filament\Actions\Action;

/**
 * Header actions for the InstallerPage.
 * The setup wizard manages its own navigation internally.
 * Only pre-wizard and post-wizard phases need header actions here.
 */
trait HasInstallerActions
{
    protected function getHeaderActions(): array
    {
        return [
            // ── pre ───────────────────────────────────────────────────────────
            Action::make('install')
                ->label('Install Webkernel')
                ->icon('heroicon-o-rocket-launch')
                ->iconPosition('after')
                ->size('sm')
                ->color('primary')
                ->visible(fn (): bool => $this->phase === 'pre')
                ->disabled(fn (): bool => ! collect($this->buildRequirements())->every(fn ($r) => $r['ok']))
                ->tooltip(fn (): ?string => collect($this->buildRequirements())->every(fn ($r) => $r['ok'])
                    ? null
                    : 'Fix failing requirements first'
                )
                ->requiresConfirmation()
                ->modalHeading('Start installation')
                ->modalDescription(
                    'This will copy .env, generate the app key, create the SQLite database, '
                    . 'run all migrations, and write deployment.php. This cannot be undone.'
                )
                ->modalSubmitActionLabel('Yes, install now')
                ->modalIcon('heroicon-o-rocket-launch')
                ->modalIconColor('primary')
                ->action('runInstall'),

            // ── verify_token ──────────────────────────────────────────────────
            Action::make('validateToken')
                ->label('Validate Token')
                ->icon('heroicon-o-shield-check')
                ->size('sm')
                ->color('primary')
                ->visible(fn (): bool => $this->phase === 'verify_token')
                ->action('runValidateToken'),

            // ── error ─────────────────────────────────────────────────────────
            Action::make('retry')
                ->label('Retry')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->visible(fn (): bool => $this->phase === 'error')
                ->action('resetToPreFlight'),

            // ── done ──────────────────────────────────────────────────────────
            Action::make('goToPanel')
                ->label('Open Webkernel')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->url('/system')
                ->visible(fn (): bool => $this->phase === 'done'),
        ];
    }
}
