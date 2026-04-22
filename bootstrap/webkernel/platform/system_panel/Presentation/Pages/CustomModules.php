<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkernel\Integration\ModuleInstaller;
use Webkernel\Integration\Registries;
use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * CustomModules
 *
 * Install Webkernel modules from any supported registry (custom/self-hosted).
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
            'registry' => Registries::defaultOption(),
            'backup'   => true,
            'hooks'    => true,
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
                Section::make('Module source')
                    ->schema([
                        Select::make('registry')
                            ->label('Registry')
                            ->options(Registries::options())
                            ->required(),
                        TextInput::make('vendor')
                            ->label('Vendor/Owner')
                            ->placeholder('acme')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Module slug')
                            ->placeholder('my-module')
                            ->required(),
                        TextInput::make('version')
                            ->label('Version')
                            ->placeholder('latest')
                            ->helperText('Leave empty for latest version'),
                        TextInput::make('token')
                            ->label('Token (optional)')
                            ->password()
                            ->helperText('GitHub PAT or GitLab token for private repositories'),
                    ])
                    ->columns(2),

                Section::make('Installation options')
                    ->schema([
                        Select::make('backup')
                            ->label('Backup existing?')
                            ->options([
                                true  => 'Yes, create backup',
                                false => 'No, replace directly',
                            ])
                            ->native(false),
                        Select::make('hooks')
                            ->label('Run hooks?')
                            ->options([
                                true  => 'Yes, execute hooks',
                                false => 'No, skip hooks',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    // ── Livewire actions ──────────────────────────────────────────────────────

    public function installModule(): void
    {
        $this->validate();

        $data = $this->form->getState();

        $registry = $data['registry'] ?? null;
        $vendor   = $data['vendor'] ?? null;
        $slug     = $data['slug'] ?? null;
        $version  = $data['version'] ?: null;
        $token    = $data['token'] ?: null;
        $backup   = (bool) ($data['backup'] ?? true);
        $hooks    = (bool) ($data['hooks'] ?? true);

        if (!$registry || !$vendor || !$slug) {
            $this->installError = 'Registry, vendor, and slug are required.';
            return;
        }

        $this->isInstalling = true;
        $this->installError = '';
        $this->installPath = '';
        $this->installStatus = 'Starting installation...';

        try {
            $installer = ModuleInstaller::module(
                from:     $registry,
                vendor:   $vendor,
                slug:     $slug,
            )
                ->withBackup($backup)
                ->withHooks($hooks);

            if ($version !== null) {
                $installer = $installer->toVersion($version);
            }

            if ($token !== null) {
                $installer = $installer->withToken($token);
            }

            $this->installStatus = 'Downloading module...';

            $path = $installer->execute();

            $this->installPath = $path;
            $this->installStatus = 'Installation complete!';
            $this->dispatch('wk-toast', type: 'success', message: "Module installed at {$path}");
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
                ->label('Install Module')
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
