<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\Concerns\InteractsWithTable;
use Filament\Tables\Concerns\HasTabs;
use Webkernel\BackOffice\System\Models\WebkernelSetting;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Widgets\SettingsStatsWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListWebkernelSettings extends ListRecords
{
    protected static string $resource = WebkernelSettingResource::class;

    public ?string $activeTab = 'all';

    protected function getHeaderWidgets(): array
    {
        return [
            SettingsStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => [
                'label' => 'All Settings',
                'badge' => WebkernelSetting::count(),
            ],
            'system' => [
                'label' => 'System',
                'badge' => WebkernelSetting::where('registry', 'webkernel')->whereNull('module')->count(),
                'icon' => 'heroicon-o-shield-check',
            ],
            'custom' => [
                'label' => 'Custom',
                'badge' => WebkernelSetting::where('is_custom', true)->count(),
                'icon' => 'heroicon-o-pencil-square',
            ],
            'modules' => [
                'label' => 'Modules',
                'badge' => WebkernelSetting::whereNotNull('module')->count(),
                'icon' => 'heroicon-o-puzzle-piece',
            ],
            'modified' => [
                'label' => 'Recently Modified',
                'badge' => WebkernelSetting::where('updated_at', '>=', now()->subDays(7))->count(),
                'icon' => 'heroicon-o-clock',
            ],
            'untouched' => [
                'label' => 'Untouched',
                'badge' => DB::table('inst_webkernel_settings')->whereRaw('value = default_value OR value IS NULL')->count(),
                'icon' => 'heroicon-o-check-circle',
            ],
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        return match ($this->activeTab) {
            'system' => $query->where('registry', 'webkernel')->whereNull('module'),
            'custom' => $query->where('is_custom', true),
            'modules' => $query->whereNotNull('module'),
            'modified' => $query->where('updated_at', '>=', now()->subDays(7))->orderByDesc('updated_at'),
            'untouched' => $query->whereRaw('value = default_value OR value IS NULL'),
            default => $query,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('defaults')
                ->label('Reset to Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Reset All Settings?')
                ->modalDescription('This will reload all default settings. Custom settings will be preserved.')
                ->modalSubmitActionLabel('Reset')
                ->action(fn() => $this->setDefaults()),
        ];
    }

    public function setDefaults(): void
    {
        WebkernelSetting::seedDefaults();

        Notification::make()
            ->title('Defaults loaded')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
