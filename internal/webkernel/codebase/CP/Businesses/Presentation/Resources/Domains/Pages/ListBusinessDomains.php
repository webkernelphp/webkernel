<?php declare(strict_types=1);

namespace Webkernel\CP\Businesses\Presentation\Resources\Domains\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\CP\Businesses\Presentation\Resources\Domains\BusinessDomainResource;

class ListBusinessDomains extends ListRecords
{
    protected static string $resource = BusinessDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
