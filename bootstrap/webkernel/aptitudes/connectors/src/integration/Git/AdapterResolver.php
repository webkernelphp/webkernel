<?php declare(strict_types=1);

namespace Webkernel\Integration\Git;

use Webkernel\Integration\Git\Contracts\GitHostAdapter;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\Integration\Git\Hosting\GitHubAdapter;
use Webkernel\Integration\Git\Hosting\GitLabAdapter;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * Resolves the correct GitHostAdapter for any Source.
 *
 * Single point that knows which adapters exist. Callers (Installer, Updater,
 * Artisan commands) only hold a reference to AdapterResolver and never import
 * individual adapter classes.
 *
 * Custom adapters can be registered at runtime via register().
 */
final class AdapterResolver
{
    /** @var list<GitHostAdapter> */
    private array $adapters = [];

    public function __construct(Token $tokenStore)
    {
        $this->adapters = [
            new GitHubAdapter($tokenStore),
            new GitLabAdapter($tokenStore),
        ];
    }

    /**
     * Register an additional adapter (prepended — checked first).
     */
    public function register(GitHostAdapter $adapter): self
    {
        array_unshift($this->adapters, $adapter);
        return $this;
    }

    /**
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
