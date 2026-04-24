<?php

namespace Webkernel\CP\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\CP\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;

class EditWebkernelSetting extends EditRecord
{
    protected static string $resource = WebkernelSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
