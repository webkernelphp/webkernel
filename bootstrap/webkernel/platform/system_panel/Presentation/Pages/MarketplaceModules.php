<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkernel\Integration\ModuleInstaller;
use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * MarketplaceModules
 *
 * Browse and install modules from the official Webkernel marketplace.
 *
 * @property-read Schema $form
 */
class MarketplaceModules extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'webkernel-system::filament.pages.marketplace-modules';

    protected static ?int                       $navigationSort           = 51;
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
            'backup' => true,
            'hooks'  => true,
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
                Section::make('Module selection')
                    ->schema([
                        TextInput::make('search')
                            ->label('Search marketplace')
                            ->placeholder('Search modules, tags, authors...')
                            ->helperText('Type to search the Webkernel marketplace'),
                    ]),

                Section::make('Installation options')
                    ->schema([
                        TextInput::make('backup')
                            ->label('Backup existing?')
                            ->default('Yes')
                            ->disabled(),
                        TextInput::make('hooks')
                            ->label('Run hooks?')
                            ->default('Yes')
                            ->disabled(),
                    ]),
            ]);
    }

    // ── Livewire actions ──────────────────────────────────────────────────────

    public function installModule(): void
    {
        $this->installError = 'Marketplace integration coming soon.';
        $this->dispatch('wk-toast', type: 'info', message: 'Marketplace integration is under development.');
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
        return 'heroicon-o-star';
    }

    public static function getNavigationLabel(): string
    {
        return 'Marketplace';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Marketplace Modules';
    }
}
