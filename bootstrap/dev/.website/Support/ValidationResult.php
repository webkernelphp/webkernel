<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Support;

class ValidationResult
{
    /** @param array<string> $errors */
    public function __construct(
        protected array $errors = [],
    ) {}

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
