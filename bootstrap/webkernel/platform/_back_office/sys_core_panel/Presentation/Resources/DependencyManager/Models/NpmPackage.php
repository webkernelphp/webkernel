<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models;

use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\NpmService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class NpmPackage extends Model
{
    use Sushi;

    protected $schema = [
        'name' => 'string',
        'type' => 'string',
        'version' => 'string',
        'latest' => 'string',
        'latest-status' => 'string',
        'description' => 'text',
        'required_by' => 'text',
        'has_update' => 'boolean',
    ];

    public function getRequiredByAttribute(): array
    {
        if (empty($this->attributes['required_by'])) {
            return [];
        }
        return array_filter(explode(',', $this->attributes['required_by']));
    }

    public function getRows(): array
    {
        return app(NpmService::class)->getAllInstalledPackages();
    }
}
