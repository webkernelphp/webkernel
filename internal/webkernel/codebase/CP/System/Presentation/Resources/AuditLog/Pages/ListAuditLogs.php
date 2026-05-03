<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\AuditLog\Pages;

use Filament\Resources\Pages\ListRecords;
use Webkernel\CP\System\Presentation\Resources\AuditLog\AuditLogResource;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
