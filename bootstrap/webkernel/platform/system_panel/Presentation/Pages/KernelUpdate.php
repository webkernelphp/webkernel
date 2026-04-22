<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkernel\Integration\KernelUpdater;
use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * KernelUpdate
 *
 * Update the Webkernel core (bootstrap/webkernel) to a new version.
 *
 * @property-read Schema $form
 */
class KernelUpdate extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'webkernel-system::filament.pages.kernel-update';

    protected static ?int                       $navigationSort           = 6;
    protected static bool                       $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null       $navigationGroup          = 'System';

    // ── Livewire state ────────────────────────────────────────────────────────

    public array  $formData = [];
    public bool   $isUpdating = false;
    public string $updateStatus = '';
    public string $updateError = '';
    public string $updatePath = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([
            'backup' => true,
            'keep'   => 'var-elements',
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
                Callout::make('warning')
                    ->heading('Kernel Update')
                    ->description('This will update the Webkernel core (bootstrap/webkernel). All running processes will be interrupted.')
                    ->danger(),

                Section::make('Update settings')
                    ->schema([
                        TextInput::make('version')
                            ->label('Target version')
                            ->placeholder('latest')
                            ->helperText('Leave empty for the latest stable version'),
                        TextInput::make('keep')
                            ->label('Preserve directories')
                            ->placeholder('var-elements')
                            ->helperText('Comma-separated list of directories to preserve (defaults to var-elements)'),
                        TextInput::make('token')
                            ->label('GitHub token (optional)')
                            ->password()
                            ->helperText('For rate limiting or private repository access'),
                    ])
                    ->columns(1),

                Section::make('Safety')
                    ->schema([
                        Select::make('backup')
                            ->label('Backup current kernel?')
                            ->options([
                                true  => 'Yes, create backup at bootstrap/webkernel.old',
                                false => 'No, replace directly (dangerous)',
                            ])
                            ->native(false),
                    ]),
            ]);
    }

    // ── Livewire actions ──────────────────────────────────────────────────────

    public function updateKernel(): void
    {
        $this->validate();

        $data = $this->form->getState();

        $version = $data['version'] ?: null;
        $token   = $data['token'] ?: null;
        $backup  = (bool) ($data['backup'] ?? true);
        $keepRaw = $data['keep'] ?? 'var-elements';
        $keepDirs = array_map(
            fn ($dir) => trim($dir),
            explode(',', $keepRaw),
        );

        $this->isUpdating = true;
        $this->updateError = '';
        $this->updatePath = '';
        $this->updateStatus = 'Starting kernel update...';

        try {
            $updater = KernelUpdater::kernel()
                ->withBackup($backup)
                ->keepDirs($keepDirs);

            if ($version !== null) {
                $updater = $updater->toVersion($version);
            }

            if ($token !== null) {
                $updater = $updater->withToken($token);
            }

            $this->updateStatus = 'Downloading new kernel...';

            $path = $updater->execute();

            $this->updatePath = $path;
            $this->updateStatus = 'Kernel updated successfully!';
            $this->dispatch('wk-toast', type: 'success', message: 'Kernel update complete. Please refresh the page.');
        } catch (NetworkException $e) {
            $this->updateError = 'Network error: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->updateError);
        } catch (\Throwable $e) {
            $this->updateError = 'Update failed: ' . $e->getMessage();
            $this->dispatch('wk-toast', type: 'error', message: $this->updateError);
        } finally {
            $this->isUpdating = false;
        }
    }

    // ── Header actions ────────────────────────────────────────────────────────

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('update')
                ->label('Update Kernel')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->disabled($this->isUpdating)
                ->requiresConfirmation()
                ->modalHeading('Update Webkernel core?')
                ->modalDescription('This will update bootstrap/webkernel and interrupt running processes.')
                ->modalSubmitActionLabel('Yes, update')
                ->action(fn () => $this->updateKernel()),
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-rocket-launch';
    }

    public static function getNavigationLabel(): string
    {
        return 'Update Kernel';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Update Webkernel Core';
    }
}
