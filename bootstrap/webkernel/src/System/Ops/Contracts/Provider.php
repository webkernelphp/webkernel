<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Contracts;

/**
 * Base provider interface for orchestration sources.
 *
 * Implementations: DatabaseProvider, ApiProvider, FileProvider, MessageProvider,
 * CloudProvider, AgentProvider, CustomProvider
 */
interface Provider
{
    /**
     * Fetch data from the source.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function fetch(): mixed;

    /**
     * Get provider name/type.
     */
    public function name(): string;

    /**
     * Get HTTP response headers (if applicable).
     *
     * @return array<string, mixed>
     */
    public function headers(): array;
}
