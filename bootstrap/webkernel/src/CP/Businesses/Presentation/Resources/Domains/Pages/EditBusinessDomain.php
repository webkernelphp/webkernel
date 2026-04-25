<?php declare(strict_types=1);

namespace Webkernel\CP\Businesses\Presentation\Resources\Domains\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\CP\Businesses\Presentation\Resources\Domains\BusinessDomainResource;

class EditBusinessDomain extends EditRecord
{
    protected static string $resource = BusinessDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
