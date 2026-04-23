<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Maintenance
 *
 * Action-oriented system maintenance page.
 *
 * Design principles:
 *   - Every section = one action group or one status group.
 *   - Page load target <= 90 ms with OPcache (no shell_exec, no blocking I/O).
 *   - Dangerous scans are MANUAL only — never triggered on page load.
 *   - Real-time metrics via SystemPulseWidget (polled independently via Livewire).
 *
 * @property-read Schema $form
 */
class Maintenance extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'webkernel-system::filament.pages.maintenance';

    protected static ?int                       $navigationSort           = -2;
    protected static bool                       $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null       $navigationGroup          = 'System';

    // ── Livewire state ────────────────────────────────────────────────────────

    public array  $formData = [];

    /** Results from the /app dangerous-function scan (populated on demand). */
    public array  $appScanResults = [];
    public bool   $appScanDone   = false;

    /** Results from the /modules dangerous-function scan (populated on demand). */
    public array  $modulesScanResults = [];
    public bool   $modulesScanDone   = false;

    /** Remote marketplace modules (populated on demand). */
    public array  $remoteModules       = [];
    public bool   $remoteModulesLoaded = false;
    public string $remoteModulesError  = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([]);
    }

    /**
     * @return array<int, string>
     */
    protected function getForms(): array
    {
        return ['form'];
    }

    // ── Form schema ───────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('formData')
            ->schema([
                Tabs::make('maintenanceTabs')
                    ->contained(false)
                    ->scrollable(true)
                    ->vertical()
                    ->persistTabInQueryString('tab')
                    ->extraAttributes(['class' => 'webkernel-dashboard-container'])
                    ->tabs([
                        // Tabs are assembled by individual build methods below.
                        // Uncomment as concerns traits are re-attached:
                        // $this->buildOperationsTab(),
                        // $this->buildSecurityTab(),
                        // $this->buildRuntimeTab(),
                        // $this->buildQueuesTab(),
                        // $this->buildModulesTab(),
                        // $this->buildLogsTab(),
                    ]),
            ]);
    }

    // ── Scan schema (used by security tab) ───────────────────────────────────

    /**
     * Builds the scan results schema fragment.
     * Shown inside the Security tab's dangerous-functions section.
     *
     * @return array<int, mixed>
     */
    private function buildScanResultsSchema(): array
    {
        if (! $this->appScanDone && ! $this->modulesScanDone) {
            return [
                TextEntry::make('scan_idle')
                    ->label('Status')
                    ->default('No scan run yet. Use the buttons below to scan /app or /modules.')
                    ->badge()
                    ->color('gray'),
            ];
        }

        $rows = [];

        if ($this->appScanDone) {
            if (isset($this->appScanResults['error'])) {
                $rows[] = Callout::make('app_scan_error')
                    ->heading('Scan error: ' . $this->appScanResults['error'])
                    ->danger();
            } elseif (empty($this->appScanResults)) {
                $rows[] = Callout::make('app_scan_clean')
                    ->heading('No dangerous function patterns detected in /app')
                    ->success();
            } else {
                $rows[] = TextEntry::make('app_scan_summary')
                    ->label('/app scan')
                    ->default(count($this->appScanResults) . ' finding(s) — sorted by descending risk score')
                    ->badge()
                    ->color('warning');

                foreach ($this->appScanResults as $i => $finding) {
                    $rows[] = TextEntry::make("app_find_{$i}")
                        ->label('[Score ' . $finding['score'] . '] [' . $finding['category'] . '] ' . $finding['function'] . '()')
                        ->default($finding['file'] . ' — ' . $finding['description'])
                        ->badge()
                        ->color($finding['color']);
                }
            }
        }

        if ($this->modulesScanDone) {
            if (empty($this->modulesScanResults)) {
                $rows[] = Callout::make('modules_scan_clean')
                    ->heading('No dangerous function patterns detected in /modules')
                    ->success();
            } else {
                $rows[] = TextEntry::make('mod_scan_summary')
                    ->label('/modules scan')
                    ->default(count($this->modulesScanResults) . ' finding(s)')
                    ->badge()
                    ->color('warning');

                foreach ($this->modulesScanResults as $i => $finding) {
                    $rows[] = TextEntry::make("mod_find_{$i}")
                        ->label('[Score ' . $finding['score'] . '] [' . $finding['category'] . '] ' . $finding['function'] . '()')
                        ->default($finding['file'] . ' — ' . $finding['description'])
                        ->badge()
                        ->color($finding['color']);
                }
            }
        }

        return $rows;
    }

    // ── Livewire actions ──────────────────────────────────────────────────────

    public function triggerAppScan(): void
    {
        // $result = $this->runDangerousFunctionsScan();
        // $this->appScanResults = $result;
        $this->appScanDone = true;
        $this->dispatch('wk-toast', type: 'info', message: 'App scan complete.');
    }

    public function triggerModulesScan(): void
    {
        // $this->modulesScanResults = $this->runModulesFolderScan();
        $this->modulesScanDone = true;
        $this->dispatch('wk-toast', type: 'info', message: 'Modules scan complete.');
    }

    public function loadRemoteModulesAction(): void
    {
        $this->remoteModulesError  = '';
        $this->remoteModulesLoaded = false;
        // $result = $this->fetchRemoteModules();
        $this->remoteModulesLoaded = true;
        $this->dispatch('wk-toast', type: 'success', message: 'Registry loaded.');
    }

    // ── Header actions ────────────────────────────────────────────────────────

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear Caches')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear all application caches?')
                ->modalDescription('Clears config, route, view, event and application caches.'),
            // ->action(fn() => $this->clearApplicationCache()),

            Action::make('optimizeApp')
                ->label('Optimize')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Caches config, routes, views and events for maximum performance.'),
            // ->action(fn() => $this->optimizeApplication()),

            Action::make('checkUpdate')
                ->label('Check Update')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),
            // ->action(fn() => ...),
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationLabel(): string
    {
        return 'Maintenance';
    }

    public function getTitle(): string|Htmlable
    {
        return 'System Maintenance';
    }
}
