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
        'description' => 'text',
        'type' => 'string',
        'has_update' => 'boolean',
    ];

    public function getRows(): array
    {
        $rows = app(ComposerService::class)->getAllInstalledPackages();
        return collect($rows)->map(fn ($row) => collect($row)->except('required_by')->toArray())->toArray();
    }
}
