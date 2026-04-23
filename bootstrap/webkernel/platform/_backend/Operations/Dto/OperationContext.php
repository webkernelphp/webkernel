<?php declare(strict_types=1);

namespace Webkernel\System\Operations\Dto;

use Webkernel\System\Operations\SourceProvider;

final class OperationContext
{
    /** @var array<int, array<string, mixed>> */
    public array $releases = [];

    public ?string $error = null;
    public bool $success = true;

    public function __construct(public readonly SourceProvider $provider) {}

    public function withError(string $error): self
    {
        $this->error = $error;
        $this->success = false;
        return $this;
    }
}
