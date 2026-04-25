<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Domains\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\CP\System\Presentation\Resources\Domains\DomainResource;

class CreateDomain extends CreateRecord
{
    protected static string $resource = DomainResource::class;
}
