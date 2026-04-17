<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Installer;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns\HasInstallerActions;
use Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns\HasInstallerForms;
use Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns\HasInstallerPhaseHandlers;
use Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns\HasInstallerSupport;
use Webkernel\Platform\SystemPanel\Support\InstallationState;
use Webkernel\System\Support\CapabilityMap;
use Filament\Notifications\Notification;

/**
 * First-run installation wizard.
 *
 * Phases:
 *   pre        — pre-flight checks (requirements + capabilities)
 *   installing — running webkernel:install
 *   verify_token — optional token gate (only if WEBKERNEL_SETUP_TOKEN env var is set)
 *   setup      — Filament Wizard: identity → account → mailer → business
 *   done       — installation complete → redirect /system
 *   error      — surface Artisan error with retry
 */
class InstallerPage extends Page
{
    use HasInstallerSupport;
    use HasInstallerForms;
    use HasInstallerActions;
    use HasInstallerPhaseHandlers;

    protected string $view = 'webkernel-system::filament.pages.installer';
    protected static string $layout = 'filament-panels::components.layout.simple';

    // ── Livewire state ────────────────────────────────────────────────────────

    /** @var 'pre'|'installing'|'verify_token'|'setup'|'done'|'error' */
    public string $phase = 'pre';

    public string $errorMessage    = '';
    public string $artisanOutput   = '';
    public string $setupTokenInput = '';

    /**
     * All wizard step data in one flat array.
     * Keys match the field names used in the Wizard steps.
     */
    public array $wizardData = [
        // Step 1 – identity
        'deployer_role' => 'owner',        // 'owner' | 'sysadmin'
        // Step 2 – account
        'name'          => '',
        'email'         => '',
        'password'      => '',
        // Step 3 – mailer (all optional)
        'smtp_host'       => '',
        'smtp_port'       => '587',
        'smtp_username'   => '',
        'smtp_password'   => '',
        'smtp_encryption' => 'tls',
        'smtp_from_name'  => '',
        'smtp_from_email' => '',
        // Step 4 – business (all optional)
        'biz_name'        => '',
        'biz_slug'        => '',
        'biz_admin_email' => '',
    ];

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        /** @disregard */
        $state = InstallationState::resolve();

        /** @disregard */
        if ($state === InstallationState::INSTALLED) {
            $this->redirect('/system');
            return;
        }

        if ($state === InstallationState::MISSING_ADMIN) {
            $this->phase = $this->resolvePostInstallPhase();

            if ($this->phase === 'verify_token') {
                $this->setupTokenInput = $this->resolveSetupToken();
            }

            Notification::make()
                ->title('Installation resumed')
                ->body('Infrastructure is ready — complete the wizard to finish setup.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function updatedPhase(string $value): void
    {
        if ($value === 'verify_token') {
            $this->setupTokenInput = $this->resolveSetupToken();
        }
    }

    // ── View data ─────────────────────────────────────────────────────────────

    public function getViewData(): array
    {
        $cap          = CapabilityMap::get();
        $requirements = $this->buildRequirements();

        return [
            'requirements'       => $requirements,
            'allRequirementsMet' => collect($requirements)->every(fn ($r) => $r['ok']),
            'capabilities'       => [
                ['id' => 'proc_fs',         'label' => '/proc filesystem', 'ok' => $cap->hasProcFs,         'help' => 'CPU load, RAM, uptime, FPM workers'],
                ['id' => 'opcache',         'label' => 'OPcache',          'ok' => $cap->hasOpcache,        'help' => 'PHP bytecode caching'],
                ['id' => 'symfony_process', 'label' => 'Symfony Process',  'ok' => $cap->hasSymfonyProcess, 'help' => 'Subprocess fallback when /proc is restricted'],
                ['id' => 'ffi',             'label' => 'FFI extension',    'ok' => $cap->hasFfi,            'help' => 'Advanced runtime introspection'],
                ['id' => 'shell_exec',      'label' => 'shell_exec',       'ok' => $cap->shellExecAllowed,  'help' => 'Shell command execution allowed'],
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

    public function getHeading(): string|Htmlable
    {
        return new HtmlString('
            <img src="' . e(webkernelBrandingUrl('webkernel-logo-light')) . '"
                 alt="Webkernel" class="fi-logo fi-logo-light">
            <img src="' . e(webkernelBrandingUrl('webkernel-logo-dark')) . '"
                 alt="Webkernel" class="fi-logo fi-logo-dark">
        ');
    }

    public function getSubheading(): ?string
    {
        return match ($this->phase) {
            'pre'          => 'Pre-flight checks — requirements and capabilities',
            'installing'   => 'Installation in progress…',
            'verify_token' => 'Enter your one-time Setup Token to continue',
            'setup'        => 'Complete the setup wizard',
            'done'         => 'Webkernel is ready',
            'error'        => 'Installation encountered an error',
            default        => null,
        };
    }
}
