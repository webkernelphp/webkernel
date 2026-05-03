<?php

namespace Webkernel\Base\Builders\DBStudio\Resources\CollectionManagerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkernel\Base\Builders\DBStudio\Resources\CollectionManagerResource;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
