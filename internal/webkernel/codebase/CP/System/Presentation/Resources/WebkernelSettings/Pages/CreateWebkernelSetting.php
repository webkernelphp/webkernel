<?php

namespace Webkernel\CP\System\Presentation\Resources\WebkernelSettings\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\CP\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;
use Webkernel\CP\System\Models\WebkernelSetting;
use Filament\Notifications\Notification;

class CreateWebkernelSetting extends CreateRecord
{
    protected static string $resource = WebkernelSettingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            WebkernelSetting::createSetting($data);

            Notification::make()
                ->title('Setting created successfully')
                ->success()
                ->send();

            return $data;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to create setting')
                ->description($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
