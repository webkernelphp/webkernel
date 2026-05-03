<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Businesses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\CP\System\Presentation\Resources\Businesses\BusinessResource;

class ListBusinesses extends ListRecords
{
    protected static string $resource = BusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
