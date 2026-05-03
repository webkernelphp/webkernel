<?php

namespace Webkernel\CP\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkernel\CP\System\Models\WebkernelSetting;
use Webkernel\CP\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;
use Webkernel\CP\System\Presentation\Resources\WebkernelSettings\Widgets\SettingsStatsWidget;
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
            'all' => Tab::make('All Settings')
                ->badge(WebkernelSetting::count()),

            'system' => Tab::make('System')
                ->icon('heroicon-o-shield-check')
                ->badge(WebkernelSetting::where('registry', 'webkernel')->whereNull('module')->count()),

            'custom' => Tab::make('Custom')
                ->icon('heroicon-o-pencil-square')
                ->badge(WebkernelSetting::where('is_custom', true)->count()),

            'modules' => Tab::make('Modules')
                ->icon('heroicon-o-puzzle-piece')
                ->badge(WebkernelSetting::whereNotNull('module')->count()),

            'modified' => Tab::make('Recently Modified')
                ->icon('heroicon-o-clock')
                ->badge(WebkernelSetting::where('updated_at', '>=', now()->subDays(7))->count()),

            'untouched' => Tab::make('Untouched')
                ->icon('heroicon-o-check-circle')
                ->badge(DB::table('instance_settings')->whereRaw('value = default_value OR value IS NULL')->count()),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery()
            ->orderBy('category')
            ->orderByRaw('CASE WHEN depends_on_key IS NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order');

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
