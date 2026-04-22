<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Http;
use UnitEnum;
use Webkernel\Integration\ModuleInstaller;
use Webkernel\Integration\Registries;
use Webkernel\Integration\RegistryCredentials;
use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * CustomModules
 *
 * Install Webkernel modules from custom registries via a guided wizard.
 *
 * @property-read Schema $form
 */
class CustomModules extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'webkernel-system::filament.pages.custom-modules';

    protected static ?int                       $navigationSort           = 52;
    protected static bool                       $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null       $navigationGroup          = 'Install Module';

    // ── Livewire state ────────────────────────────────────────────────────────

    public array  $formData = [];
    public bool   $isInstalling = false;
    public string $installStatus = '';
    public string $installError = '';
    public string $installPath = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([
            'registry' => Registries::GitHub->value,
            'backup'   => true,
            'hooks'    => true,
            'save_token' => false,
        ]);
    }

    /** @return array<int, string> */
    protected function getForms(): array
    {
        return ['form'];
    }

    // ── Form schema ───────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('formData')
            ->alignment(Alignment::Start)
            ->schema([
                Wizard::make([
                    Step::make('Registry')
                        ->icon('heroicon-o-rectangle-stack')
                        ->description('Choose where to install from')
                        ->schema([
                            Select::make('registry')
                                ->label('Registry')
                                ->options(Registries::customOptions())
                                ->required()
                                ->native(false)
                                ->createOptionForm([
                                    TextInput::make('label')
                                        ->label('Registry Hostname')
                                        ->placeholder('registry.example.com')
                                        ->helperText('e.g., git.example.com, registry.internal.io')
                                        ->required()
                                        ->regex('/^[a-z0-9.\-]+$/', 'Lowercase, dots, and hyphens only'),
                                ])
                                ->createOptionUsing(function (array $data): string {
                                    $hostname = $data['label'];
                                    $url = 'https://' . trim($hostname, '/') . '/api';

                                    try {
                                        $response = Http::timeout(5)->head($url);
                                        if ($response->status() >= 400) {
                                            $this->dispatch('wk-toast', type: 'warning', message: "Registry is private - token will be required");
                                        }
                                        return Registries::Custom->value . ':' . $hostname;
                                    } catch (\Throwable $e) {
                                        throw new \Exception("Registry not found at {$url}. Check the hostname and try again.");
                                    }
                                }),
                        ]),

                    Step::make('Module Details')
                        ->icon('heroicon-o-cube')
                        ->description('Specify which module to install')
                        ->schema([
                            TextInput::make('vendor')
                                ->label('Vendor/Owner')
                                ->placeholder('acme')
                                ->required()
                                ->helperText('GitHub username, organization, or vendor slug'),
                            TextInput::make('slug')
                                ->label('Module slug')
                                ->placeholder('my-module')
                                ->required()
                                ->helperText('Repository or module name'),
                            TextInput::make('version')
                                ->label('Version (optional)')
                                ->placeholder('latest')
                                ->helperText('Leave empty for latest version'),
                        ])
                        ->columns(1),

                    Step::make('Credentials')
                        ->icon('heroicon-o-key')
                        ->description('Authentication if needed')
                        ->schema([
                            TextInput::make('token')
                                ->label('Token (optional)')
                                ->password()
                                ->helperText('GitHub PAT, GitLab token, or API key for private repositories'),
                            Toggle::make('save_token')
                                ->label('Save token for future use')
                                ->helperText('Encrypted and stored securely'),
                        ])
                        ->columns(1),

                    Step::make('Confirm')
                        ->icon('heroicon-o-check-circle')
                        ->description('Review and install')
                        ->schema([
                            TextInput::make('vendor')
                                ->label('Vendor/Owner')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('slug')
                                ->label('Module')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('version')
                                ->label('Version')
                                ->disabled()
                                ->dehydrated(false),
                            Toggle::make('backup')
                                ->label('Create backup')
                                ->default(true)
                                ->helperText('Recommended: backs up existing installation'),
                            Toggle::make('hooks')
                                ->label('Run installation hooks')
                                ->default(true)
                                ->helperText('Execute any setup scripts included in the module'),
                        ])
                        ->columns(1),
                ])
                ->persistStepInQueryString('custom-module-step')
                ->contained(false),
            ]);
    }

    // ── Livewire actions ──────────────────────────────────────────────────────

    public function installModule(): void
    {
        $this->validate();

        $data = $this->form->getState();

        $registryValue = $data['registry'] ?? null;
        $vendor   = $data['vendor'] ?? null;
        $slug     = $data['slug'] ?? null;
        $version  = $data['version'] ?: null;
        $token    = $data['token'] ?: null;
        $backup   = (bool) ($data['backup'] ?? true);
        $hooks    = (bool) ($data['hooks'] ?? true);
        $saveToken = (bool) ($data['save_token'] ?? false);

        if (!$registryValue || !$vendor || !$slug) {
            $this->installError = 'Registry, vendor, and slug are required.';
            return;
        }

        $this->isInstalling = true;
        $this->installError = '';
        $this->installPath = '';
        $this->installStatus = 'Starting installation...';

        try {
            // Handle custom registry format: "custom:hostname"
            $registry = $registryValue;
            if (str_contains($registryValue, ':')) {
                [$registryKey, $hostname] = explode(':', $registryValue, 2);
                $registry = $registryKey;
            }

            $registryEnum = Registries::tryFrom($registry);

            if (!$registryEnum) {
                throw new \InvalidArgumentException("Unknown registry: {$registry}");
            }

            $installer = ModuleInstaller::module(
                from:   $registry,
                vendor: $vendor,
                slug:   $slug,
            )
                ->withBackup($backup)
                ->withHooks($hooks);

            if ($version !== null) {
                $installer = $installer->toVersion($version);
            }

            if ($token !== null) {
                $installer = $installer->withToken($token);

                if ($saveToken) {
                    RegistryCredentials::store($registryEnum, $token, $vendor);
                }
            }

            $this->installStatus = 'Downloading module...';

            $path = $installer->execute();

            $this->installPath = $path;
            $this->installStatus = 'Installation complete!';
            $this->dispatch('wk-toast', type: 'success', message: "Module installed at {$path}");

            $this->form->fill([
                'registry' => Registries::GitHub->value,
                'backup'   => true,
                'hooks'    => true,
                'save_token' => false,
            ]);
        } catch (NetworkException $e) {
            $this->installError = 'Network error: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->installError);
        } catch (\Throwable $e) {
            $this->installError = 'Installation failed: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->installError);
        } finally {
            $this->isInstalling = false;
        }
    }

    // ── Header actions ────────────────────────────────────────────────────────

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('install')
                ->label('Install')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->disabled($this->isInstalling)
                ->action(fn () => $this->installModule()),
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-cube-transparent';
    }

    public static function getNavigationLabel(): string
    {
        return 'Custom Modules';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Install Custom Module';
    }
}
