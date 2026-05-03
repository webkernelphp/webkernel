<?php

namespace Webkernel\Base\Builders\DBStudio\Filtering;

interface FilterNode
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
