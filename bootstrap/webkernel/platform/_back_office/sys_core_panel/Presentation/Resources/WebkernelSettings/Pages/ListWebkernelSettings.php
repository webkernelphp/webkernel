<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Tabs\Tab;
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
            'all' => Tab::make('All Settings')
                ->badge(WebkernelSetting::count()),

            'system' => Tab::make('System')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('registry', 'webkernel')->whereNull('module'))
                ->badge(WebkernelSetting::where('registry', 'webkernel')->whereNull('module')->count()),

            'custom' => Tab::make('Custom')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_custom', true))
                ->badge(WebkernelSetting::where('is_custom', true)->count()),

            'modules' => Tab::make('Modules')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('module'))
                ->badge(WebkernelSetting::whereNotNull('module')->count()),

            'modified' => Tab::make('Recently Modified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('updated_at', '>=', now()->subDays(7))->orderByDesc('updated_at'))
                ->badge(WebkernelSetting::where('updated_at', '>=', now()->subDays(7))->count()),

            'untouched' => Tab::make('Untouched')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('value = default_value OR value IS NULL'))
                ->badge(DB::table('inst_webkernel_settings')->whereRaw('value = default_value OR value IS NULL')->count()),
        ];
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
