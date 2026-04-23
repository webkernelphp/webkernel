<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;

class CreateWebkernelSetting extends CreateRecord
{
    protected static string $resource = WebkernelSettingResource::class;
}
