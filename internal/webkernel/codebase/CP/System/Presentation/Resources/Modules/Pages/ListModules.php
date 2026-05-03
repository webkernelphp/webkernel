<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Modules\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\CP\System\Presentation\Resources\Modules\ModuleResource;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
