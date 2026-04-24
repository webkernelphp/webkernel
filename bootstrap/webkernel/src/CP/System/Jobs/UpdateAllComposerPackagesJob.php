<?php declare(strict_types=1);

namespace Webkernel\CP\System\Jobs;

use Webkernel\Jobs\ComposerJob;

class UpdateAllComposerPackagesJob extends ComposerJob
{
    public function __construct(string $taskId)
    {
        parent::__construct($taskId, self::STRATEGY_UPDATE_ALL);
    }
}
