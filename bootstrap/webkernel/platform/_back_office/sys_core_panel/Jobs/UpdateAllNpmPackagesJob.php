<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Jobs;

use Webkernel\Jobs\NpmJob;

class UpdateAllNpmPackagesJob extends NpmJob
{
    public function __construct(string $taskId)
    {
        parent::__construct($taskId, self::STRATEGY_UPDATE_ALL);
    }
}
