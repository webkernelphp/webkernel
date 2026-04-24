<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Dto;

/**
 * Result of a single operation step.
 *
 * Each step in a do() pipeline produces a StepResult that can be
 * chained or inspected.
 */
final class StepResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $stepName,
        public readonly mixed  $output = null,
        public readonly ?string $error = null,
    ) {}

    public static function ok(string $stepName, mixed $output = null): self
    {
        return new self(
            success: true,
            stepName: $stepName,
            output: $output,
        );
    }

    public static function failed(string $stepName, string $error): self
    {
        return new self(
            success: false,
            stepName: $stepName,
            error: $error,
        );
    }
}
