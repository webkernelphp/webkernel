<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Domains\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\CP\System\Presentation\Resources\Domains\DomainResource;

class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
