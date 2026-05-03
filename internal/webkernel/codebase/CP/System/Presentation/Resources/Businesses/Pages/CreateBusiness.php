<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Businesses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\CP\System\Presentation\Resources\Businesses\BusinessResource;

class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;
}
