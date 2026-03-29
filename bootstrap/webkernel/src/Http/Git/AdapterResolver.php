<?php declare(strict_types=1);

namespace Webkernel\Http\Git;

use Webkernel\Http\Git\Contracts\GitHostAdapter;
use Webkernel\Http\Git\Exceptions\NetworkException;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * Resolves the correct GitHostAdapter for any Source.
 *
 * This is the single point that knows which adapters exist.
 * Callers (Updater, Installer) only hold a reference to AdapterResolver
 * and never need to import individual adapter classes.
 *
 * Custom adapters can be registered at runtime via register().
 */
final class AdapterResolver
{
    /** @var list<GitHostAdapter> */
    private array $adapters = [];

    public function __construct(Token $tokenStore)
    {
        // Default adapters in resolution priority order
        $this->adapters = [
            new GitHubAdapter($tokenStore),
            new GitLabAdapter($tokenStore),
        ];
    }

    /**
     * Register an additional adapter.
     * Custom adapters are checked first (prepended).
     */
    public function register(GitHostAdapter $adapter): self
    {
        array_unshift($this->adapters, $adapter);
        return $this;
    }

    /**
     * Return the first adapter that supports the given Source.
     *
     * @throws NetworkException  when no adapter supports the source
     */
    public function resolve(Source $source): GitHostAdapter
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($source)) {
                return $adapter;
            }
        }

        throw new NetworkException(
            "No adapter registered for registry [{$source->registry}]. "
            . "Register a custom GitHostAdapter via AdapterResolver::register()."
        );
    }
}
