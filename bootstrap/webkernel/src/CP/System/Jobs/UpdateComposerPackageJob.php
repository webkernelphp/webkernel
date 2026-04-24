<?php declare(strict_types=1);

namespace Webkernel\CP\System\Jobs;

use Webkernel\Jobs\ComposerJob;

class UpdateComposerPackageJob extends ComposerJob
{
    public function __construct(
        string $taskId,
        string $packageName,
        string $version,
    ) {
        parent::__construct($taskId, self::STRATEGY_REQUIRE, $packageName, $version);
    }
}
