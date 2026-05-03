<?php

namespace Webkernel\CP\System\Presentation\Resources\DependencyManager\Models;

use Webkernel\CP\System\Presentation\Resources\DependencyManager\Services\NpmService;
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
        'has_update' => 'boolean',
    ];

    public function getRows(): array
    {
        $rows = app(NpmService::class)->getAllInstalledPackages();
        return collect($rows)->map(fn ($row) => collect($row)->except('required_by')->toArray())->toArray();
    }
}
