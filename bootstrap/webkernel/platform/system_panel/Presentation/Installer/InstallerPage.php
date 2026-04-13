<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Installer;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Webkernel\Platform\SystemPanel\Support\InstallationState;
use Webkernel\System\Support\CapabilityMap;
use Webkernel\Users\UserPrivilege;
use Webkernel\Users\Models\UserPrivilegeModel;
use App\Models\User;

/**
 * First-run installation wizard page.
 *
 * Lives inside the no-auth installer panel (/installer).
 * Redirects to /system once installation is complete.
 *
 * Phases:
 *   pre          -- pre-flight checks: requirements + capabilities
 *   installing   -- running webkernel:install (synchronous Artisan call)
 *   create_user  -- form to create the first admin account
 *   done         -- install complete, redirect button
 *   error        -- Artisan command threw, surface error with retry option
 *
 * Resume behaviour:
 *   If infrastructure is ready but no privileged user exists
 *   (e.g. a previous run crashed after webkernel:install but before
 *   runCreateAdmin), mount() sets phase = 'create_user' directly so the
 *   operator can finish without re-running the Artisan command.
 *
 *   If everything is fully installed the page redirects to /system.
 */
class InstallerPage extends Page
{
    protected string $view = 'webkernel-system::filament.pages.installer';
    protected static string $layout = 'filament-panels::components.layout.simple';

    // -------------------------------------------------------------------------
    // Livewire state
    // -------------------------------------------------------------------------

    /** @var 'pre'|'installing'|'create_user'|'done'|'error' */
    public string $phase = 'pre';

    public string $errorMessage  = '';
    public string $artisanOutput = '';

    /** Bound to the Filament form (phase create_user). */
    public ?array $adminData = [
        'name'      => '',
        'email'     => '',
        'password'  => '',
        'privilege' => 'app-owner',
    ];

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $state = InstallationState::resolve();

        if ($state === InstallationState::INSTALLED) {
            // Everything is in order -- nothing to do here.
            $this->redirect('/system');
            return;
        }

        if ($state === InstallationState::MISSING_ADMIN) {
            // Infrastructure is ready but no privileged user exists.
            // Skip the Artisan step and go straight to account creation.
            $this->phase = 'create_user';

            Notification::make()
                ->title('Installation resumed')
                ->body(
                    'The platform infrastructure is ready but no Application Owner or '
                    . 'Super Administrator account has been created yet. '
                    . 'Please create one now to complete setup.'
                )
                ->warning()
                ->persistent()
                ->send();
        }

        // InstallationState::NOT_INSTALLED -- stay on phase 'pre', normal flow.
    }

    // -------------------------------------------------------------------------
    // Filament v4 form
    // -------------------------------------------------------------------------

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('adminData')
            ->components([
                TextInput::make('name')
                    ->label('Full name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('John Doe'),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'users', column: 'email')
                    ->placeholder('admin@example.com'),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->maxLength(255),

                Select::make('privilege')
                    ->label('Role')
                    ->options(self::privilegedOptions())
                    ->default(UserPrivilege::APP_OWNER->value)
                    ->required()
                    ->live()
                    ->helperText(fn (?string $state): string =>
                        UserPrivilege::fromKey($state ?? '')?->description() ?? ''
                    ),
            ]);
    }

    // -------------------------------------------------------------------------
    // Header actions
    // -------------------------------------------------------------------------

    protected function getHeaderActions(): array
    {
        return [
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
                    'This will: copy .env, generate the app key, create the SQLite database, '
                    . 'run all migrations, and write deployment.php. This action cannot be undone.'
                )
                ->modalSubmitActionLabel('Yes, install now')
                ->modalIcon('heroicon-o-rocket-launch')
                ->modalIconColor('primary')
                ->action('runInstall'),

            Action::make('createAdmin')
                ->label('Create account & finish')
                ->icon('heroicon-o-user-plus')
                ->size('sm')
                ->color('success')
                ->visible(fn (): bool => $this->phase === 'create_user')
                ->action('runCreateAdmin'),

            Action::make('retry')
                ->label('Retry')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->visible(fn (): bool => $this->phase === 'error')
                ->action('resetToPreFlight'),

            Action::make('goToPanel')
                ->label('Open Webkernel')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->url('/system')
                ->visible(fn (): bool => $this->phase === 'done'),
        ];
    }

    // -------------------------------------------------------------------------
    // Livewire methods
    // -------------------------------------------------------------------------

    public function runInstall(): void
    {
        if ($this->phase !== 'pre') {
            return;
        }

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
            $this->phase         = 'create_user';

            Notification::make()
                ->title('Installation successful')
                ->body('Now create the first administrator account.')
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

    public function runCreateAdmin(): void
    {
        if ($this->phase !== 'create_user') {
            return;
        }

        $data = $this->form->getState();

        // Validate that the chosen privilege is app-owner or super-user.
        // Members cannot be the first account -- that would leave the platform
        // permanently unmanageable.
        $privilege = UserPrivilege::fromKey($data['privilege'] ?? '');

        if ($privilege === null || $privilege === UserPrivilege::MEMBER) {
            Notification::make()
                ->title('Invalid role')
                ->body(
                    'The first account must be an Application Owner or Super Administrator. '
                    . 'A Member account cannot manage the platform.'
                )
                ->danger()
                ->send();
            return;
        }

        try {
            /** @var User $user */
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            UserPrivilegeModel::create([
                'user_id'   => $user->id,
                'privilege' => $privilege->value,
            ]);

            $this->phase = 'done';

            Notification::make()
                ->title('Account created')
                ->body(sprintf(
                    '%s has been created as %s.',
                    $user->name,
                    $privilege->label(),
                ))
                ->success()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not create account')
                ->body($e->getMessage())
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

    // -------------------------------------------------------------------------
    // View data
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Navigation
    // -------------------------------------------------------------------------

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
                 alt="Webkernel"
                 class="fi-logo fi-logo-light">
            <img src="' . e(webkernelBrandingUrl('webkernel-logo-dark')) . '"
                 alt="Webkernel"
                 class="fi-logo fi-logo-dark">
        ');
    }

    public function getSubheading(): ?string
    {
        return match ($this->phase) {
            'pre'         => 'Run pending migrations in order and store host profile',
            'installing'  => 'Installation in progress...',
            'create_user' => 'Create the first administrator account',
            'done'        => 'Webkernel is ready',
            'error'       => 'Installation encountered an error',
            default       => null,
        };
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    /**
     * Only app-owner and super-user are valid choices for the first account.
     * Offering 'member' here would leave the platform without any administrator.
     *
     * @return array<string, string>
     */
    private static function privilegedOptions(): array
    {
        return [
            UserPrivilege::APP_OWNER->value  => UserPrivilege::APP_OWNER->label(),
            UserPrivilege::SUPER_USER->value => UserPrivilege::SUPER_USER->label(),
        ];
    }

    /**
     * @return list<array{id: string, label: string, ok: bool, value: string}>
     */
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
