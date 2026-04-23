<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Tabs\Tab;
use Webkernel\BackOffice\System\Models\WebkernelSetting;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListWebkernelSettings extends ListRecords
{
    protected static string $resource = WebkernelSettingResource::class;

    public ?string $activeTab = 'all';

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

            'recently_modified' => Tab::make('Recently Modified')
                ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('updated_at')->limit(50))
                ->badge(WebkernelSetting::where('updated_at', '>=', now()->subDays(7))->count()),

            'modules' => Tab::make('Modules')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('module'))
                ->badge(WebkernelSetting::whereNotNull('module')->count()),

            'untouched' => Tab::make('Untouched')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('value', '=', DB::raw('default_value'))->orWhereNull('value'))
                ->badge(WebkernelSetting::whereRaw('value = default_value OR value IS NULL')->count()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('defaults')
                ->label('Set Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Reset to Default Settings?')
                ->modalDescription('This will reload all default settings from the system.')
                ->modalSubmitActionLabel('Reset')
                ->action(fn() => $this->setDefaults()),
        ];
    }

    public function setDefaults(): void
    {
        WebkernelSetting::seedDefaults();

        Notification::make()
            ->title('Default settings loaded')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
