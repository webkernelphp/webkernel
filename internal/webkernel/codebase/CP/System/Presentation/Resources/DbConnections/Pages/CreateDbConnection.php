<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\DbConnections\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\CP\System\Presentation\Resources\DbConnections\DbConnectionResource;

class CreateDbConnection extends CreateRecord
{
    protected static string $resource = DbConnectionResource::class;
}
