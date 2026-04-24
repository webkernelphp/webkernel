<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Jobs;

use Webkernel\Jobs\NpmJob;

class UpdateNpmPackageJob extends NpmJob
{
    public function __construct(
        string $taskId,
        string $packageName,
        string $version,
    ) {
        parent::__construct($taskId, self::STRATEGY_INSTALL, $packageName, $version);
    }
}
