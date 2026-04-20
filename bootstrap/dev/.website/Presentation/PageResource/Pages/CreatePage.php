<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Presentation\PageResource\Pages;

use Webkernel\Builders\Website\Presentation\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
