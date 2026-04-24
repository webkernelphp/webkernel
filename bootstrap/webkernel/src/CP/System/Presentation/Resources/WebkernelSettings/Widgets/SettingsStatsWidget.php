<?php

namespace Webkernel\CP\System\Presentation\Resources\WebkernelSettings\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkernel\CP\System\Models\WebkernelSetting;
use Illuminate\Support\Facades\DB;

class SettingsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Settings', WebkernelSetting::count())
                ->description('All configured settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary'),

            Stat::make('System Settings', WebkernelSetting::where('registry', 'webkernel')->whereNull('module')->count())
                ->description('Core Webkernel')
                ->icon('heroicon-o-shield-check')
                ->color('info'),

            Stat::make('Custom Settings', WebkernelSetting::where('is_custom', true)->count())
                ->description('User-created')
                ->icon('heroicon-o-pencil-square')
                ->color('success'),

            Stat::make('Module Settings', WebkernelSetting::whereNotNull('module')->count())
                ->description('From modules')
                ->icon('heroicon-o-puzzle-piece')
                ->color('warning'),

            Stat::make('Recently Modified', WebkernelSetting::where('updated_at', '>=', now()->subDays(7))->count())
                ->description('Last 7 days')
                ->icon('heroicon-o-clock')
                ->color('gray'),

            Stat::make('Untouched', DB::table('inst_webkernel_settings')->whereRaw('value = default_value OR value IS NULL')->count())
                ->description('Using defaults')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
