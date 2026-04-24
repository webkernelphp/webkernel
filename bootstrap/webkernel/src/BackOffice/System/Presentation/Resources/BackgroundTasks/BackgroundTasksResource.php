<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\BackgroundTasks;

use Filament\Resources\Resource;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\BackgroundTasks\Pages\ListBackgroundTasks;
use BackedEnum;
use UnitEnum;

class BackgroundTasksResource extends Resource
{
    protected static ?string $model = WebkernelBackgroundTask::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';
    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';


    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'background-tasks';

    public static function getNavigationBadge(): ?string
    {
        $count = WebkernelBackgroundTask::active()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackgroundTasks::route('/'),
        ];
    }
}
