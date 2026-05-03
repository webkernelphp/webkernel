<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Modules\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\CP\System\Presentation\Resources\Modules\ModuleResource;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;
}
