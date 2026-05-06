<?php declare(strict_types=1);

namespace Webkernel\CP\Installer\Presentation\Installer;

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
use Webkernel\Base\Businesses\Models\Business;
use Webkernel\Base\Connectors\Mailer;
use Webkernel\CP\Installer\Constants\InstallerConfig;
use Webkernel\CP\Installer\Constants\InstallationPhase;
use Webkernel\CP\Installer\Presentation\Installer\InstallationState;
use Webkernel\Base\System\Host\Support\CapabilityMap;
use Webkernel\Base\Users\Enums\UserPrivilegeLevel;
use Webkernel\Base\Users\Models\User;
use Throwable;
use RuntimeException;

/**
 * First-run installation wizard.
 */
final class InstallerPage extends Page
{
    protected string $view = 'webkernel-installer::filament.pages.installer';
    protected static string $layout = 'filament-panels::components.layout.simple';

    // ─────────────────────────────────────────────────────────────────
    // STATE
    // ─────────────────────────────────────────────────────────────────

    public string $phase = 'pre';
    public string $errorMessage = '';
    public string $artisanOutput = '';
    public string $setupTokenInput = '';

    public string $deployer_role = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $smtp_host = '';
    public string $smtp_port = '';
    public string $smtp_username = '';
    public string $smtp_password = '';
    public string $smtp_encryption = '';
    public string $smtp_from_name = '';
    public string $smtp_from_email = '';
    public string $biz_name = '';
    public string $biz_slug = '';
    public string $biz_admin_email = '';

    // ─────────────────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Mount the installer page.
     */
    public function mount(): void
    {
        try {
            $this->deployer_role = UserPrivilegeLevel::APP_OWNER->value;
            $this->smtp_port = InstallerConfig::MAILER_DEFAULT_PORT;
            $this->smtp_encryption = InstallerConfig::MAILER_DEFAULT_ENCRYPTION;

            $state = InstallationState::resolve();

            if ($state === InstallationState::INSTALLED) {
                $this->redirect('/system');
                return;
            }

            if ($state === InstallationState::MISSING_ADMIN) {
                $this->phase = $this->resolvePostInstallPhase();

                if ($this->phase === InstallationPhase::VERIFY_TOKEN->value) {
                    $this->setupTokenInput = $this->resolveSetupToken();
                }

                Notification::make()
                    ->title(InstallerConfig::NOTIFICATION_INSTALLATION_RESUMED)
                    ->body(InstallerConfig::SUCCESS_INSTALLATION_RESUMED)
                    ->warning()
                    ->persistent()
                    ->send();
            }
        } catch (Throwable $e) {
            $this->phase = InstallationPhase::ERROR->value;
            $this->errorMessage = $e->getMessage();
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────

    /**
     * Build the form schema based on current phase.
     */
    public function form(Schema $schema): Schema
    {
        return match ($this->phase) {
            InstallationPhase::VERIFY_TOKEN->value => $this->buildTokenForm($schema),
            InstallationPhase::SETUP->value => $this->buildSetupForm($schema),
            default => $schema->components([]),
        };
    }

    /**
     * Build token verification form.
     */
    private function buildTokenForm(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('setupTokenInput')
                ->label('Setup Token')
                ->password()
                ->revealable()
                ->required()
                ->placeholder('One-time token from your environment'),
        ]);
    }

    /**
     * Build setup wizard form.
     */
    private function buildSetupForm(Schema $schema): Schema
    {
        return $schema->components([
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
                            ->maxLength(InstallerConfig::USER_NAME_MAX_LENGTH)
                            ->placeholder('Your Name'),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(InstallerConfig::USER_EMAIL_MAX_LENGTH)
                            ->unique(table: 'users', column: 'email')
                            ->placeholder('you@example.com'),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(InstallerConfig::PASSWORD_MIN_LENGTH)
                            ->maxLength(InstallerConfig::PASSWORD_MAX_LENGTH)
                            ->columnSpanFull(),
                    ]),
                Step::make('Mailer')
                    ->icon('heroicon-o-envelope')
                    ->description('Optional')
                    ->columns(2)
                    ->schema([
                        TextInput::make('smtp_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.example.com'),
                        TextInput::make('smtp_port')
                            ->label('Port')
                            ->default(InstallerConfig::MAILER_DEFAULT_PORT),
                        TextInput::make('smtp_username')
                            ->label('Username'),
                        TextInput::make('smtp_password')
                            ->label('Password')
                            ->password()
                            ->revealable(),
                        Select::make('smtp_encryption')
                            ->label('Encryption')
                            ->options(InstallerConfig::MAILER_ENCRYPTIONS)
                            ->default(InstallerConfig::MAILER_DEFAULT_ENCRYPTION)
                            ->native(false),
                        TextInput::make('smtp_from_email')
                            ->label('From Email')
                            ->email(),
                        TextInput::make('smtp_from_name')
                            ->label('From Name')
                            ->columnSpanFull(),
                    ]),
                Step::make('Business')
                    ->icon('heroicon-o-building-office')
                    ->description('Optional')
                    ->columns(2)
                    ->schema([
                        TextInput::make('biz_name')
                            ->label('Business Name')
                            ->maxLength(InstallerConfig::BUSINESS_NAME_MAX_LENGTH)
                            ->placeholder('Acme Corp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn (string|null $state, callable $set): mixed => filled($state)
                                    ? $set('biz_slug', strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $state), '-')))
                                    : null
                            ),
                        TextInput::make('biz_slug')
                            ->label('Slug')
                            ->maxLength(InstallerConfig::BUSINESS_SLUG_MAX_LENGTH)
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
                ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                    <x-filament::button
                        type="button"
                        wire:click="runCompleteSetup"
                        wire:loading.attr="disabled"
                        style="background-color: var(--primary-600);"
                        size="sm"
                    >
                        <span wire:loading.remove wire:target="runCompleteSetup">{{ $label }}</span>
                        <span wire:loading wire:target="runCompleteSetup">{{ $completing }}</span>
                    </x-filament::button>
                BLADE, [
                    'label' => InstallerConfig::BUTTON_COMPLETE,
                    'completing' => InstallerConfig::BUTTON_COMPLETING,
                ]))),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTIONS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Get header actions.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->buildInstallAction(),
            $this->buildValidateTokenAction(),
            $this->buildRetryAction(),
        ];
    }

    /**
     * Build install button action.
     */
    private function buildInstallAction(): Action
    {
        return Action::make('install')
            ->label(InstallerConfig::BUTTON_INSTALL)
            ->icon('heroicon-o-rocket-launch')
            ->iconPosition('after')
            ->size('sm')
            ->color('primary')
            ->visible(fn (): bool => $this->phase === InstallationPhase::PRE->value)
            ->disabled(fn (): bool => !collect($this->buildRequirements())->every(fn (array $r): bool => $r['ok']))
            ->tooltip(fn (): string|null => collect($this->buildRequirements())->every(fn (array $r): bool => $r['ok'])
                ? null
                : 'Fix failing requirements first'
            )
            ->requiresConfirmation()
            ->modalHeading(InstallerConfig::CONFIRMATION_HEADING)
            ->modalDescription(InstallerConfig::CONFIRMATION_BODY)
            ->modalSubmitActionLabel(InstallerConfig::CONFIRMATION_SUBMIT)
            ->modalIcon('heroicon-o-rocket-launch')
            ->modalIconColor('primary')
            ->action('runInstall');
    }

    /**
     * Build validate token button action.
     */
    private function buildValidateTokenAction(): Action
    {
        return Action::make('validateToken')
            ->label(InstallerConfig::BUTTON_VALIDATE)
            ->icon('heroicon-o-shield-check')
            ->size('sm')
            ->color('primary')
            ->visible(fn (): bool => $this->phase === InstallationPhase::VERIFY_TOKEN->value)
            ->action('runValidateToken');
    }

    /**
     * Build retry button action.
     */
    private function buildRetryAction(): Action
    {
        return Action::make('retry')
            ->label(InstallerConfig::BUTTON_RETRY)
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->outlined()
            ->visible(fn (): bool => $this->phase === InstallationPhase::ERROR->value)
            ->action('resetToPreFlight');
    }

    // ─────────────────────────────────────────────────────────────────
    // PHASE HANDLERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Run the installation process.
     */
    public function runInstall(): void
    {
        if ($this->phase !== InstallationPhase::PRE->value) {
            return;
        }

        if (!collect($this->buildRequirements())->every(fn (array $r): bool => $r['ok'])) {
            Notification::make()
                ->title(InstallerConfig::NOTIFICATION_REQUIREMENTS_NOT_MET)
                ->body(InstallerConfig::ERROR_REQUIREMENTS_NOT_MET)
                ->danger()
                ->send();
            return;
        }

        try {
            $this->validateWritableDirectories();

            $this->phase = InstallationPhase::INSTALLING->value;

            Artisan::call('webkernel:install');
            $this->artisanOutput = Artisan::output();
            $this->phase = $this->resolvePostInstallPhase();

            if ($this->phase === InstallationPhase::VERIFY_TOKEN->value) {
                $this->setupTokenInput = $this->resolveSetupToken();
            }

            Notification::make()
                ->title(InstallerConfig::NOTIFICATION_INFRASTRUCTURE_READY)
                ->body(InstallerConfig::SUCCESS_INSTALLATION_COMPLETE)
                ->success()
                ->send();
        } catch (Throwable $e) {
            $this->phase = InstallationPhase::ERROR->value;
            $this->errorMessage = $e->getMessage();
            Notification::make()
                ->title(InstallerConfig::NOTIFICATION_INSTALLATION_FAILED)
                ->body(InstallerConfig::ERROR_INSTALLATION_FAILED)
                ->danger()
                ->send();
        }
    }

    /**
     * Validate the setup token.
     */
    public function runValidateToken(): void
    {
        if ($this->phase !== InstallationPhase::VERIFY_TOKEN->value) {
            return;
        }

        if (!hash_equals($this->resolveSetupToken(), (string)$this->setupTokenInput)) {
            Notification::make()
                ->title(InstallerConfig::NOTIFICATION_INVALID_TOKEN)
                ->body(InstallerConfig::ERROR_INVALID_TOKEN)
                ->danger()
                ->send();
            return;
        }

        if (file_exists(InstallerConfig::TOKEN_FILE)) {
            @unlink(InstallerConfig::TOKEN_FILE);
        }

        $this->phase = InstallationPhase::SETUP->value;
    }

    /**
     * Complete the setup process.
     */
    public function runCompleteSetup(): void
    {
        if ($this->phase !== InstallationPhase::SETUP->value) {
            return;
        }

        try {
            // Create user
            $role = UserPrivilegeLevel::tryFrom($this->deployer_role) ?? UserPrivilegeLevel::APP_OWNER;
            $user = webkernel()->users()->createWithPrivilege(
                name: $this->name,
                email: $this->email,
                password: $this->password,
                level: $role,
            );

            // Configure mailer (non-critical)
            if (filled($this->smtp_host)) {
                try {
                    Mailer::configure([
                        'host' => $this->smtp_host,
                        'port' => $this->smtp_port,
                        'username' => $this->smtp_username,
                        'password' => $this->smtp_password,
                        'encryption' => $this->smtp_encryption,
                        'from_name' => $this->smtp_from_name,
                        'from_email' => $this->smtp_from_email,
                    ]);
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(InstallerConfig::NOTIFICATION_MAILER_NOT_SAVED)
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                }
            }

            // Create business (non-critical)
            if (filled($this->biz_name)) {
                try {
                    $slug = filled($this->biz_slug) ? $this->biz_slug : Str::slug($this->biz_name);
                    $business = Business::create([
                        'name' => $this->biz_name,
                        'slug' => $slug,
                        'admin_email' => $this->biz_admin_email,
                        'created_by' => $user->getKey(),
                    ]);

                    // Send invitation (non-critical)
                    if (Mailer::isConfigured() && filled($this->biz_admin_email)) {
                        try {
                            Mailer::sendHtml(
                                to: $this->biz_admin_email,
                                subject: sprintf(InstallerConfig::EMAIL_SUBJECT_INVITATION, e($business->name)),
                                html: sprintf(
                                    InstallerConfig::EMAIL_BODY_INVITATION,
                                    e($business->name),
                                    url('/'),
                                    url('/'),
                                ),
                            );
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(InstallerConfig::NOTIFICATION_INVITATION_NOT_SENT)
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }
                } catch (Throwable $e) {
                    Notification::make()
                        ->title(InstallerConfig::NOTIFICATION_BUSINESS_NOT_CREATED)
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                }
            }

            Notification::make()
                ->title(sprintf(InstallerConfig::SUCCESS_SETUP_COMPLETE, $user->name))
                ->success()
                ->persistent()
                ->send();

            $this->redirect('/system', navigate: true);
        } catch (Throwable $e) {
            Notification::make()
                ->title(InstallerConfig::NOTIFICATION_SETUP_FAILED)
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Reset to pre-flight checks.
     */
    public function resetToPreFlight(): void
    {
        $this->phase = InstallationPhase::PRE->value;
        $this->errorMessage = '';
        $this->artisanOutput = '';
    }

    // ─────────────────────────────────────────────────────────────────
    // VIEW DATA
    // ─────────────────────────────────────────────────────────────────

    /**
     * Get view data.
     *
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $cap = CapabilityMap::get();
        $requirements = $this->buildRequirements();

        return [
            'requirements' => $requirements,
            'allRequirementsMet' => collect($requirements)->every(fn (array $r): bool => $r['ok']),
            'capabilities' => [
                ['id' => 'proc_fs', 'label' => '/proc filesystem', 'ok' => $cap->hasProcFs, 'help' => 'CPU load, RAM, uptime, FPM workers'],
                ['id' => 'opcache', 'label' => 'OPcache', 'ok' => $cap->hasOpcache, 'help' => 'PHP bytecode caching'],
                ['id' => 'symfony_process', 'label' => 'Symfony Process', 'ok' => $cap->hasSymfonyProcess, 'help' => 'Subprocess fallback when /proc is restricted'],
                ['id' => 'ffi', 'label' => 'FFI extension', 'ok' => $cap->hasFfi, 'help' => 'Advanced runtime introspection'],
                ['id' => 'shell_exec', 'label' => 'shell_exec', 'ok' => $cap->shellExecAllowed, 'help' => 'Shell command execution allowed'],
            ],
            'profile' => $cap->profile,
            'hostname' => php_uname('n'),
            'phpVersion' => PHP_VERSION,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // NAVIGATION
    // ─────────────────────────────────────────────────────────────────

    /**
     * Get navigation icon.
     */
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    /**
     * Get navigation label.
     */
    public static function getNavigationLabel(): string
    {
        return 'Setup';
    }

    /**
     * Get page title.
     */
    public function getTitle(): string|Htmlable
    {
        return 'Webkernel Setup';
    }

    /**
     * Get page heading.
     */
    public function getHeading(): string|Htmlable
    {
        return new HtmlString(
            '<img src="' . e(webkernelBrandingUrl('webkernel-logo-light')) . '" alt="Webkernel" class="fi-logo fi-logo-light">'
            . '<img src="' . e(webkernelBrandingUrl('webkernel-logo-dark')) . '" alt="Webkernel" class="fi-logo fi-logo-dark">'
        );
    }

    /**
     * Get page subheading.
     */
    public function getSubheading(): ?string
    {
        return match ($this->phase) {
            InstallationPhase::PRE->value => InstallerConfig::SUBHEADING_PRE,
            InstallationPhase::INSTALLING->value => InstallerConfig::SUBHEADING_INSTALLING,
            InstallationPhase::VERIFY_TOKEN->value => InstallerConfig::SUBHEADING_VERIFY_TOKEN,
            InstallationPhase::SETUP->value => InstallerConfig::SUBHEADING_SETUP,
            InstallationPhase::ERROR->value => InstallerConfig::SUBHEADING_ERROR,
            default => null,
        };
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Resolve post-installation phase.
     */
    private function resolvePostInstallPhase(): string
    {
        $envToken = config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN');
        return empty($envToken) ? InstallationPhase::SETUP->value : InstallationPhase::VERIFY_TOKEN->value;
    }

    /**
     * Resolve or generate setup token.
     *
     * @throws RuntimeException
     */
    private function resolveSetupToken(): string
    {
        $envToken = config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN');

        if (!empty($envToken)) {
            return (string)$envToken;
        }

        if (!file_exists(InstallerConfig::TOKEN_FILE)) {
            if (!is_dir(InstallerConfig::TOKEN_DIR)) {
                if (!@mkdir(InstallerConfig::TOKEN_DIR, InstallerConfig::TOKEN_DIR_PERMISSIONS, true)) {
                    throw new RuntimeException(
                        sprintf(InstallerConfig::ERROR_TOKEN_DIR_CREATE_FAILED, InstallerConfig::TOKEN_DIR)
                    );
                }
            }

            $token = Str::random(InstallerConfig::TOKEN_LENGTH);

            if (!@file_put_contents(InstallerConfig::TOKEN_FILE, $token, LOCK_EX)) {
                throw new RuntimeException(
                    sprintf(InstallerConfig::ERROR_TOKEN_FILE_CREATE_FAILED, InstallerConfig::TOKEN_FILE)
                );
            }

            @chmod(InstallerConfig::TOKEN_FILE, InstallerConfig::TOKEN_FILE_PERMISSIONS);
        }

        $content = @file_get_contents(InstallerConfig::TOKEN_FILE);

        if ($content === false) {
            throw new RuntimeException(
                sprintf(InstallerConfig::ERROR_TOKEN_FILE_READ_FAILED, InstallerConfig::TOKEN_FILE)
            );
        }

        return trim($content);
    }

    /**
     * Validate that required directories exist and are writable.
     *
     * @throws RuntimeException
     */
    private function validateWritableDirectories(): void
    {
        foreach (InstallerConfig::REQUIRED_DIRECTORIES as $path => $name) {
            if (!is_dir($path)) {
                throw new RuntimeException(
                    sprintf(InstallerConfig::ERROR_DIRECTORY_NOT_EXIST, $path)
                );
            }

            if (!is_writable($path)) {
                throw new RuntimeException(
                    sprintf(InstallerConfig::ERROR_DIRECTORY_NOT_WRITABLE, $path)
                );
            }
        }
    }

    /**
     * Build system requirements list.
     *
     * @return array<int, array{id: string, label: string, ok: bool, value: string}>
     */
    private function buildRequirements(): array
    {
        return [
            [
                'id' => 'php',
                'label' => 'PHP >= 8.4',
                'ok' => version_compare(PHP_VERSION, '8.4.0', '>='),
                'value' => PHP_VERSION,
            ],
            [
                'id' => 'openssl',
                'label' => 'OpenSSL extension',
                'ok' => extension_loaded('openssl'),
                'value' => '',
            ],
            [
                'id' => 'pdo',
                'label' => 'PDO extension',
                'ok' => extension_loaded('pdo'),
                'value' => '',
            ],
            [
                'id' => 'mbstring',
                'label' => 'Mbstring extension',
                'ok' => extension_loaded('mbstring'),
                'value' => '',
            ],
            [
                'id' => 'xml',
                'label' => 'XML / DOM extension',
                'ok' => extension_loaded('xml'),
                'value' => '',
            ],
            [
                'id' => 'json',
                'label' => 'JSON extension',
                'ok' => extension_loaded('json'),
                'value' => '',
            ],
            [
                'id' => 'ctype',
                'label' => 'Ctype extension',
                'ok' => extension_loaded('ctype'),
                'value' => '',
            ],
            [
                'id' => 'bcmath',
                'label' => 'BCMath extension',
                'ok' => extension_loaded('bcmath'),
                'value' => '',
            ],
            [
                'id' => 'storage',
                'label' => 'storage/ writable',
                'ok' => is_writable(storage_path()),
                'value' => '',
            ],
            [
                'id' => 'cache',
                'label' => 'cache/ writable',
                'ok' => is_writable(base_path('internal/cache')),
                'value' => '',
            ],
        ];
    }
}
