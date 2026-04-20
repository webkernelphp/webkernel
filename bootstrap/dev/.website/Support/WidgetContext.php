<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Support;

use Webkernel\Builders\Website\Models\Page;

class WidgetContext
{
    public function __construct(
        public readonly ?Page $page,
        public readonly ?string $rowId,
        public readonly ?string $columnId,
        public readonly ?string $widgetId,
    ) {}
}
