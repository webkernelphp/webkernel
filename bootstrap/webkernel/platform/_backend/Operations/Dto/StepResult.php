<?php declare(strict_types=1);

namespace Webkernel\System\Operations\Dto;

final class StepResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $error): self
    {
        return new self(false, $error);
    }
}
