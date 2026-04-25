<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Businesses\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\CP\System\Presentation\Resources\Businesses\BusinessResource;

class EditBusiness extends EditRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
