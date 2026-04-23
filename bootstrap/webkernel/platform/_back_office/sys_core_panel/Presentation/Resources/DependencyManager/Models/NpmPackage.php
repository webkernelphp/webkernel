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
        'description' => 'string',
    ];

    public function getRows(): array
    {
        return app(NpmService::class)->getOutdatedPackages();
    }
}
