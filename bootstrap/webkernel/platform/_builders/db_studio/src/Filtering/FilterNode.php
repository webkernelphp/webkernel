<?php

namespace Webkernel\Builders\DBStudio\Filtering;

interface FilterNode
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
