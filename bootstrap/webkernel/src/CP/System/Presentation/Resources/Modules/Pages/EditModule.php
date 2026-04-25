<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Modules\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\CP\System\Presentation\Resources\Modules\ModuleResource;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
