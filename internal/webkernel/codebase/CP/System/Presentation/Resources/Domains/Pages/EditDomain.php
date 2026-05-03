<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Domains\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\CP\System\Presentation\Resources\Domains\DomainResource;

class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
