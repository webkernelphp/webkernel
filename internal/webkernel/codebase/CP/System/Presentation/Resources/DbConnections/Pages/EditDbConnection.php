<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\DbConnections\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\CP\System\Presentation\Resources\DbConnections\DbConnectionResource;

class EditDbConnection extends EditRecord
{
    protected static string $resource = DbConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
