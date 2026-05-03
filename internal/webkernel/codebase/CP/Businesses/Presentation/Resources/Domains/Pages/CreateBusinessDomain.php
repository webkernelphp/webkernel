<?php declare(strict_types=1);

namespace Webkernel\CP\Businesses\Presentation\Resources\Domains\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\CP\Businesses\Presentation\Resources\Domains\BusinessDomainResource;

class CreateBusinessDomain extends CreateRecord
{
    protected static string $resource = BusinessDomainResource::class;
}
