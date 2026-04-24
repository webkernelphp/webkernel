<?php declare(strict_types=1);

namespace Webkernel\BackOffice\Installer\Presentation\Installer;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Webkernel\Businesses\Models\Business;
use Webkernel\Connectors\Mailer;
use Webkernel\BackOffice\Installer\Presentation\Installer\InstallationState;
use Webkernel\System\Host\Support\CapabilityMap;
use Webkernel\Users\Enum\UserPrivilegeLevel;
use Webkernel\Users\Models\User;

/**
 * First-run installation wizard.
 *
 * Phases:
 *   pre          — pre-flight checks
 *   installing   — running webkernel:install
 *   verify_token — optional token gate
 *   setup        — Wizard: role → account → mailer → business
 *   error        — surface error with retry
 */
class InstallerPage extends Page
{
    protected string $view         = 'webkernel-installer::filament.pages.installer';
    protected static string $layout = 'filament-panels::components.layout.simple';

    // ── Livewire state ────────────────────────────────────────────────────────

    /** @var 'pre'|'installing'|'verify_token'|'setup'|'error' */
    public string $phase = 'pre';

    public string $errorMessage    = '';
    public string $artisanOutput   = '';
    public string $setupTokenInput = '';

    // Wizard fields — flat, no nesting
    public string $deployer_role    = '';
    public string $name             = '';
    public string $email            = '';
    public string $password         = '';
    public string $smtp_host        = '';
    public string $smtp_port        = '587';
    public string $smtp_username    = '';
    public string $smtp_password    = '';
    public string $smtp_encryption  = 'tls';
    public string $smtp_from_name   = '';
    public string $smtp_from_email  = '';
    public string $biz_name         = '';
    public string $biz_slug         = '';
    public string $biz_admin_email  = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->deployer_role = UserPrivilegeLevel::APP_OWNER->value;

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

    // ── Form ──────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return match ($this->phase) {
            'verify_token' => $schema->components([
                TextInput::make('setupTokenInput')
                    ->label('Setup Token')
                    ->password()
                    ->revealable()
                    ->required()
                    ->placeholder('One-time token from your environment'),
            ]),

            'setup' => $schema->components([
                Wizard::make([
                    Step::make('Who are you?')
                        ->icon('heroicon-o-identification')
                        ->description('Mandatory')
                        ->schema([
                            Radio::make('deployer_role')
                                ->label('')
                                ->options(webkernel()->users()->installerRoleOptions())
                                ->descriptions(webkernel()->users()->installerRoleDescriptions())
                                ->default(UserPrivilegeLevel::APP_OWNER->value)
                                ->required()
                                ->live()
                                ->extraAttributes(['class' => 'wds-claim-radio']),
                        ]),

                    Step::make('Your Account')
                        ->icon('heroicon-o-user-circle')
                        ->description('Mandatory')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Full name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Your Name'),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(table: 'users', column: 'email')
                                ->placeholder('you@example.com'),
                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(12)
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Mailer')
                        ->icon('heroicon-o-envelope')
                        ->description('Optional')
                        ->columns(2)
                        ->schema([
                            TextInput::make('smtp_host')->label('SMTP Host')->placeholder('smtp.example.com'),
                            TextInput::make('smtp_port')->label('Port')->default('587'),
                            TextInput::make('smtp_username')->label('Username'),
                            TextInput::make('smtp_password')->label('Password')->password()->revealable(),
                            Select::make('smtp_encryption')
                                ->label('Encryption')
                                ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'])
                                ->default('tls')
                                ->native(false),
                            TextInput::make('smtp_from_email')->label('From Email')->email(),
                            TextInput::make('smtp_from_name')->label('From Name')->columnSpanFull(),
                        ]),

                    Step::make('Business')
                        ->icon('heroicon-o-building-office')
                        ->description('Optional')
                        ->columns(2)
                        ->schema([
                            TextInput::make('biz_name')
                                ->label('Business Name')
                                ->maxLength(255)
                                ->placeholder('Acme Corp')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) => filled($state)
                                    ? $set('biz_slug', strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $state), '-')))
                                    : null
                                ),
                            TextInput::make('biz_slug')
                                ->label('Slug')
                                ->maxLength(63)
                                ->placeholder('acme-corp')
                                ->helperText('Lowercase, hyphens only.'),
                            TextInput::make('biz_admin_email')
                                ->label('Business-Admin Email')
                                ->email()
                                ->placeholder('admin@example.com')
                                ->helperText('Invite sent by email once mailer is configured.')
                                ->columnSpanFull(),
                        ]),
                ])
                    ->persistStepInQueryString('installer-step')
                    ->contained(false)
                    ->submitAction(new HtmlString(Blade::render(<<<BLADE
                        <x-filament::button
                            type="button"
                            wire:click="runCompleteSetup"
                            wire:loading.attr="disabled"
                            style="background-color: var(--primary-600);"
                            size="sm"
                        >
                            <span wire:loading.remove wire:target="runCompleteSetup">Complete setup</span>
                            <span wire:loading wire:target="runCompleteSetup">Setting up…</span>
                        </x-filament::button>
                    BLADE))),
            ]),

            default => $schema->components([]),
        };
    }

    // ── Header actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('install')
                ->label('Install Webkernel')
                ->icon('heroicon-o-rocket-launch')
                ->iconPosition('after')
                ->size('sm')
                ->color('primary')
                ->visible(fn () => $this->phase === 'pre')
                ->disabled(fn () => ! collect($this->buildRequirements())->every(fn ($r) => $r['ok']))
                ->tooltip(fn () => collect($this->buildRequirements())->every(fn ($r) => $r['ok'])
                    ? null : 'Fix failing requirements first'
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

            Action::make('validateToken')
                ->label('Validate Token')
                ->icon('heroicon-o-shield-check')
                ->size('sm')
                ->color('primary')
                ->visible(fn () => $this->phase === 'verify_token')
                ->action('runValidateToken'),

            Action::make('retry')
                ->label('Retry')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->visible(fn () => $this->phase === 'error')
                ->action('resetToPreFlight'),
        ];
    }

    // ── Phase handlers ────────────────────────────────────────────────────────

    public function runInstall(): void
    {
        if ($this->phase !== 'pre') return;

        if (! collect($this->buildRequirements())->every(fn ($r) => $r['ok'])) {
            Notification::make()->title('Requirements not met')->danger()->send();
            return;
        }

        $this->phase = 'installing';

        try {
            Artisan::call('webkernel:install');
            $this->artisanOutput = Artisan::output();
            $this->phase         = $this->resolvePostInstallPhase();

            if ($this->phase === 'verify_token') {
                $this->setupTokenInput = $this->resolveSetupToken();
            }

            Notification::make()->title('Infrastructure ready')->success()->send();
        } catch (\Throwable $e) {
            $this->phase        = 'error';
            $this->errorMessage = $e->getMessage();
            Notification::make()->title('Installation failed')->danger()->send();
        }
    }

    public function runValidateToken(): void
    {
        if ($this->phase !== 'verify_token') return;

        if (! hash_equals($this->resolveSetupToken(), (string) $this->setupTokenInput)) {
            Notification::make()->title('Invalid Setup Token')->danger()->send();
            return;
        }

        $tokenFile = storage_path('webkernel/.setup_token');
        if (file_exists($tokenFile)) {
            @unlink($tokenFile);
        }

        $this->phase = 'setup';
    }

    public function runCompleteSetup(): void
    {
        if ($this->phase !== 'setup') return;

        try {
            $role = UserPrivilegeLevel::tryFrom($this->deployer_role) ?? UserPrivilegeLevel::APP_OWNER;

            /** @var User $user */
            $user = webkernel()->users()->createWithPrivilege(
                name:     $this->name,
                email:    $this->email,
                password: $this->password,
                level:    $role,
            );

            if (filled($this->smtp_host)) {
                try {
                    Mailer::configure([
                        'host'       => $this->smtp_host,
                        'port'       => $this->smtp_port,
                        'username'   => $this->smtp_username,
                        'password'   => $this->smtp_password,
                        'encryption' => $this->smtp_encryption,
                        'from_name'  => $this->smtp_from_name,
                        'from_email' => $this->smtp_from_email,
                    ]);
                } catch (\Throwable $e) {
                    Notification::make()->title('Mailer not saved')->body($e->getMessage())->warning()->send();
                }
            }

            if (filled($this->biz_name)) {
                try {
                    $slug     = filled($this->biz_slug) ? $this->biz_slug : Str::slug($this->biz_name);
                    $business = Business::create([
                        'name'        => $this->biz_name,
                        'slug'        => $slug,
                        'admin_email' => $this->biz_admin_email,
                        'created_by'  => $user->getKey(),
                    ]);

                    if (Mailer::isConfigured() && filled($this->biz_admin_email)) {
                        Mailer::sendHtml(
                            to:      $this->biz_admin_email,
                            subject: 'You have been invited to manage ' . $business->name,
                            html:    sprintf(
                                '<p>You have been invited to manage <strong>%s</strong>.</p><p><a href="%s">%s</a></p>',
                                e($business->name), url('/'), url('/'),
                            ),
                        );
                    }
                } catch (\Throwable $e) {
                    Notification::make()->title('Business not created')->body($e->getMessage())->warning()->send();
                }
            }

            Notification::make()
                ->title(sprintf('Welcome, %s — setup complete', $user->name))
                ->success()
                ->persistent()
                ->send();

            $this->redirect('/system', navigate: true);

        } catch (\Throwable $e) {
            Notification::make()->title('Setup failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function resetToPreFlight(): void
    {
        $this->phase        = 'pre';
        $this->errorMessage = '';
        $this->artisanOutput = '';
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

    public static function getNavigationLabel(): string { return 'Setup'; }

    public function getTitle(): string|Htmlable { return 'Webkernel Setup'; }

    public function getHeading(): string|Htmlable
    {
        return new HtmlString('
            <img src="' . e(webkernelBrandingUrl('webkernel-logo-light')) . '" alt="Webkernel" class="fi-logo fi-logo-light">
            <img src="' . e(webkernelBrandingUrl('webkernel-logo-dark')) . '"  alt="Webkernel" class="fi-logo fi-logo-dark">
        ');
    }

    public function getSubheading(): ?string
    {
        return match ($this->phase) {
            'pre'          => 'Pre-flight checks — requirements and capabilities',
            'installing'   => 'Installation in progress…',
            'verify_token' => 'Enter your one-time Setup Token to continue',
            'setup'        => 'Complete the setup wizard',
            'error'        => 'Installation encountered an error',
            default        => null,
        };
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolvePostInstallPhase(): string
    {
        $envToken = config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN');
        return empty($envToken) ? 'setup' : 'verify_token';
    }

    private function resolveSetupToken(): string
    {
        $envToken = config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN');
        if (! empty($envToken)) {
            return (string) $envToken;
        }

        $tokenFile = storage_path('webkernel/.setup_token');
        if (! file_exists($tokenFile)) {
            $dir = dirname($tokenFile);
            if (! is_dir($dir)) mkdir($dir, 0700, true);
            file_put_contents($tokenFile, Str::random(48), LOCK_EX);
            chmod($tokenFile, 0600);
        }

        return trim((string) file_get_contents($tokenFile));
    }

    /** @return list<array{id: string, label: string, ok: bool, value: string}> */
    private function buildRequirements(): array
    {
        return [
            ['id' => 'php',      'label' => 'PHP >= 8.4',                'ok' => version_compare(PHP_VERSION, '8.4.0', '>='), 'value' => PHP_VERSION],
            ['id' => 'openssl',  'label' => 'OpenSSL extension',         'ok' => extension_loaded('openssl'),                 'value' => ''],
            ['id' => 'pdo',      'label' => 'PDO extension',             'ok' => extension_loaded('pdo'),                     'value' => ''],
            ['id' => 'mbstring', 'label' => 'Mbstring extension',        'ok' => extension_loaded('mbstring'),                'value' => ''],
            ['id' => 'xml',      'label' => 'XML / DOM extension',       'ok' => extension_loaded('xml'),                     'value' => ''],
            ['id' => 'json',     'label' => 'JSON extension',            'ok' => extension_loaded('json'),                    'value' => ''],
            ['id' => 'ctype',    'label' => 'Ctype extension',           'ok' => extension_loaded('ctype'),                   'value' => ''],
            ['id' => 'bcmath',   'label' => 'BCMath extension',          'ok' => extension_loaded('bcmath'),                  'value' => ''],
            ['id' => 'storage',  'label' => 'storage/ writable',         'ok' => is_writable(storage_path()),                 'value' => ''],
            ['id' => 'cache',    'label' => 'bootstrap/cache/ writable', 'ok' => is_writable(base_path('bootstrap/cache')),   'value' => ''],
        ];
    }
}
