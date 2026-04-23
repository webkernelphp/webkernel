<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models;

use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class ComposerPackage extends Model
{
    use Sushi;

    protected $schema = [
        'name' => 'string',
        'version' => 'string',
        'latest' => 'string',
        'latest-status' => 'string',
        'latest-release-date' => 'string',
        'description' => 'string',
    ];

    public function getRows(): array
    {
        return app(ComposerService::class)->getOutdatedPackages();
    }
}
