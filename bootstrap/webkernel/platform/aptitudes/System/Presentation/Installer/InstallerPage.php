<?php declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Presentation\Installer;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\{Page,SimplePage};
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Webkernel\System\Support\CapabilityMap;

/**
 * First-run installation wizard page.
 *
 * Lives inside the no-auth installer panel (/install).
 * Redirects to /system once installation is complete.
 *
 * Phases:
 *   pre        — pre-flight checks: requirements + capabilities
 *   installing — running webkernel:install (synchronous Artisan call)
 *   done       — install complete, show output + redirect button
 *   error      — Artisan command threw, surface error with retry option
 */
class InstallerPage extends Page
{
    protected string $view = 'webkernel-system::filament.pages.installer';
    protected static string $layout = 'filament-panels::components.layout.simple';

    // ── Livewire state ────────────────────────────────────────────────────────

    /** @var 'pre'|'installing'|'done'|'error' */
    public string $phase         = 'pre';
    public string $errorMessage  = '';
    public string $artisanOutput = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        if ($this->isAlreadyInstalled()) {
            $this->redirect('/system');
        }
    }

    // ── Header actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            // Install — only in pre phase, disabled when requirements fail
            Action::make('install')
                ->label('Install Webkernel')
                ->icon('heroicon-o-rocket-launch')
                ->color('primary')
                ->visible(fn (): bool => $this->phase === 'pre')
                ->disabled(fn (): bool => ! collect($this->buildRequirements())->every(fn ($r) => $r['ok']))
                ->tooltip(fn (): ?string => collect($this->buildRequirements())->every(fn ($r) => $r['ok'])
                    ? null
                    : 'Fix failing requirements first')
                ->requiresConfirmation()
                ->modalHeading('Start installation')
                ->modalDescription(
                    'This will: copy .env, generate the app key, create the SQLite database, run all migrations, and write deployment.php. This action cannot be undone.'
                )
                ->modalSubmitActionLabel('Yes, install now')
                ->modalIcon('heroicon-o-rocket-launch')
                ->modalIconColor('primary')
                ->action('runInstall'),

            // Retry — only in error phase
            Action::make('retry')
                ->label('Retry')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->visible(fn (): bool => $this->phase === 'error')
                ->action('resetToPreFlight'),

            // Open panel — only in done phase
            Action::make('goToPanel')
                ->label('Open Webkernel')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->url('/system')
                ->visible(fn (): bool => $this->phase === 'done'),
        ];
    }

    // ── Livewire methods ──────────────────────────────────────────────────────

    public function runInstall(): void
    {
        if ($this->phase !== 'pre') {
            return;
        }

        // Double-check requirements server-side before running
        $requirements = $this->buildRequirements();
        if (! collect($requirements)->every(fn ($r) => $r['ok'])) {
            Notification::make()
                ->title('Requirements not met')
                ->body('Fix the failing checks before installing.')
                ->danger()
                ->send();
            return;
        }

        $this->phase = 'installing';

        try {
            Artisan::call('webkernel:install');
            $this->artisanOutput = Artisan::output();
            $this->phase         = 'done';

            Notification::make()
                ->title('Webkernel installed')
                ->body('All steps completed successfully.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->phase        = 'error';
            $this->errorMessage = $e->getMessage();

            Notification::make()
                ->title('Installation failed')
                ->body('Check the error output for details.')
                ->danger()
                ->send();
        }
    }

    public function resetToPreFlight(): void
    {
        $this->phase         = 'pre';
        $this->errorMessage  = '';
        $this->artisanOutput = '';
    }

    // ── View data ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $cap          = CapabilityMap::get();
        $requirements = $this->buildRequirements();

        return [
            'requirements'       => $requirements,
            'allRequirementsMet' => collect($requirements)->every(fn ($r) => $r['ok']),
            'capabilities'       => [
                ['id' => 'proc_fs',         'label' => '/proc filesystem',  'ok' => $cap->hasProcFs,         'help' => 'CPU load, RAM, uptime, FPM workers'],
                ['id' => 'opcache',         'label' => 'OPcache',           'ok' => $cap->hasOpcache,        'help' => 'PHP bytecode caching'],
                ['id' => 'symfony_process', 'label' => 'Symfony Process',   'ok' => $cap->hasSymfonyProcess, 'help' => 'Subprocess fallback when /proc is restricted'],
                ['id' => 'ffi',             'label' => 'FFI extension',     'ok' => $cap->hasFfi,            'help' => 'Advanced runtime introspection'],
                ['id' => 'shell_exec',      'label' => 'shell_exec',        'ok' => $cap->shellExecAllowed,  'help' => 'Shell command execution allowed'],
            ],
            'profile'    => $cap->profile,
            'hostname'   => php_uname('n'),
            'phpVersion' => PHP_VERSION,
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationLabel(): string
    {
        return 'Setup';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Webkernel Setup';
    }

    public function getSubheading(): ?string
    {
        return 'Run pending migrations in order and store host profile';
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function isAlreadyInstalled(): bool
    {
        if (! is_file(base_path('.env'))) {
            return false;
        }

        $key = trim((string) config('app.key', ''));

        return $key !== ''
            && str_starts_with($key, 'base64:')
            && strlen($key) > 30
            && is_file(base_path('deployment.php'));
    }

    /**
     * @return list<array{id: string, label: string, ok: bool, value: string}>
     */
    private function buildRequirements(): array
    {
        return [
            ['id' => 'php',      'label' => 'PHP ≥ 8.4',                  'ok' => version_compare(PHP_VERSION, '8.4.0', '>='), 'value' => PHP_VERSION],
            ['id' => 'openssl',  'label' => 'OpenSSL extension',          'ok' => extension_loaded('openssl'),                 'value' => ''],
            ['id' => 'pdo',      'label' => 'PDO extension',              'ok' => extension_loaded('pdo'),                     'value' => ''],
            ['id' => 'mbstring', 'label' => 'Mbstring extension',         'ok' => extension_loaded('mbstring'),                'value' => ''],
            ['id' => 'xml',      'label' => 'XML / DOM extension',        'ok' => extension_loaded('xml'),                     'value' => ''],
            ['id' => 'json',     'label' => 'JSON extension',             'ok' => extension_loaded('json'),                    'value' => ''],
            ['id' => 'ctype',    'label' => 'Ctype extension',            'ok' => extension_loaded('ctype'),                   'value' => ''],
            ['id' => 'bcmath',   'label' => 'BCMath extension',           'ok' => extension_loaded('bcmath'),                  'value' => ''],
            ['id' => 'storage',  'label' => 'storage/ writable',         'ok' => is_writable(storage_path()),                 'value' => ''],
            ['id' => 'cache',    'label' => 'bootstrap/cache/ writable', 'ok' => is_writable(base_path('bootstrap/cache')),   'value' => ''],
        ];
    }
}
