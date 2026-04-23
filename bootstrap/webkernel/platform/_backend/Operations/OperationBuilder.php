<?php declare(strict_types=1);

namespace Webkernel\System\Operations;

use Webkernel\System\Operations\Dto\OperationContext;
use Webkernel\System\Operations\Dto\StepResult;
use Webkernel\System\Operations\SourceProviderWithMetadata;

/**
 * Fluent builder for source operations — releases, artifacts, metadata.
 *
 * Supports:
 *   - Default steps for fetching releases
 *   - Custom steps via step() before/after defaults
 *   - Closures for extensibility
 *   - Conditional execution via when()
 *   - Side-effects via gap()
 *
 * Usage:
 *   webkernel()->do()
 *       ->from(GitHubSourceProvider::forWebkernel())
 *       ->stepBefore('Validate', fn($ctx) => ...)
 *       ->run()
 */
final class OperationBuilder
{
    /** @var list<array{label: string, closure: \Closure, _pre: bool}> */
    private array $steps = [];

    /** @var list<\Closure(OperationContext):void> */
    private array $beforeHooks = [];

    /** @var list<\Closure(OperationContext):void> */
    private array $afterHooks = [];

    /** @var list<\Closure():void> */
    private array $gaps = [];

    private ?SourceProvider $provider = null;
    private bool $runDefaults = true;
    private bool $cacheEnabled = false;
    private bool $downloadEnabled = false;
    private ?string $downloadDir = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the source provider for this operation.
     */
    public function from(SourceProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Register a custom step to run before defaults.
     *
     * @param \Closure(OperationContext):bool|string $closure
     */
    public function stepBefore(string $label, \Closure $closure): self
    {
        $this->steps[] = [
            'label' => $label,
            'closure' => $closure,
            '_pre' => true,
        ];
        return $this;
    }

    /**
     * Register a custom step to run after defaults.
     *
     * @param \Closure(OperationContext):bool|string $closure
     */
    public function stepAfter(string $label, \Closure $closure): self
    {
        $this->steps[] = [
            'label' => $label,
            'closure' => $closure,
            '_pre' => false,
        ];
        return $this;
    }

    /**
     * Skip default steps entirely — only run custom steps.
     */
    public function skipDefaults(): self
    {
        $this->runDefaults = false;
        return $this;
    }

    /**
     * Register a hook to run before any steps.
     *
     * @param \Closure(OperationContext):void $closure
     */
    public function before(\Closure $closure): self
    {
        $this->beforeHooks[] = $closure;
        return $this;
    }

    /**
     * Register a hook to run after all steps complete successfully.
     *
     * @param \Closure(OperationContext):void $closure
     */
    public function after(\Closure $closure): self
    {
        $this->afterHooks[] = $closure;
        return $this;
    }

    /**
     * Register a gap — silent side-effect closure injected between steps.
     *
     * @param \Closure():void $closure
     */
    public function gap(\Closure $closure): self
    {
        $this->gaps[] = $closure;
        return $this;
    }

    /**
     * Enable caching of fetch results.
     */
    public function withCache(int $ttlSeconds = 3600): self
    {
        $this->cacheEnabled = true;
        // TODO: implement cache TTL handling
        return $this;
    }

    /**
     * Enable downloading of artifacts.
     */
    public function download(string $targetDir): self
    {
        $this->downloadEnabled = true;
        $this->downloadDir = $targetDir;
        return $this;
    }

    /**
     * Conditional helper — calls $closure($this) only when condition is true.
     *
     * @param \Closure(self):mixed $closure
     */
    public function when(bool $condition, \Closure $closure): self
    {
        if ($condition) $closure($this);
        return $this;
    }

    /**
     * Execute the operation.
     */
    public function run(): OperationContext
    {
        if ($this->provider === null) {
            throw new \RuntimeException('No source provider configured — call from() first.');
        }

        $context = new OperationContext($this->provider);

        // Run before hooks
        foreach ($this->beforeHooks as $hook) {
            $hook($context);
        }

        // Separate pre and post steps
        $preSteps = array_filter($this->steps, static fn ($s) => $s['_pre']);
        $postSteps = array_filter($this->steps, static fn ($s) => !$s['_pre']);

        // Execute pre-steps
        foreach ($preSteps as $step) {
            $result = $this->executeStep($context, $step);
            if (!$result->success) {
                return $context->withError($result->error);
            }
        }

        // Run default steps if enabled
        if ($this->runDefaults) {
            $result = $this->runDefaultSteps($context);
            if (!$result->success) {
                return $context->withError($result->error);
            }
        }

        // Execute post-steps
        foreach ($postSteps as $step) {
            $result = $this->executeStep($context, $step);
            if (!$result->success) {
                return $context->withError($result->error);
            }
        }

        // Run after hooks only if all steps succeeded
        foreach ($this->afterHooks as $hook) {
            $hook($context);
        }

        return $context;
    }

    /**
     * @param array{label: string, closure: \Closure} $step
     */
    private function executeStep(OperationContext $context, array $step): StepResult
    {
        try {
            // Run any registered gaps
            foreach ($this->gaps as $gap) {
                $gap();
            }

            $result = ($step['closure'])($context);

            if ($result === true || $result === '') {
                return StepResult::success();
            }

            if (is_string($result)) {
                return StepResult::failure($result);
            }

            return StepResult::failure('Step returned unexpected value');
        } catch (\Throwable $e) {
            return StepResult::failure($e->getMessage());
        }
    }

    private function runDefaultSteps(OperationContext $context): StepResult
    {
        // Default step: fetch releases from provider
        $releases = $this->provider->releases();
        if (empty($releases)) {
            return StepResult::failure('No releases found from source');
        }

        $context->releases = $releases;

        // Default step: fetch metadata if provider supports it
        if ($this->provider instanceof SourceProviderWithMetadata) {
            foreach ($releases as &$release) {
                $metadata = $this->provider->metadata($release);
                if ($metadata !== null) {
                    $release['metadata'] = $metadata;
                }
            }
        }

        // Default step: download if enabled
        if ($this->downloadEnabled && $this->downloadDir !== null) {
            foreach ($releases as $release) {
                if (isset($release['artifact_url'])) {
                    try {
                        $this->provider->download($release['artifact_url'], $this->downloadDir);
                    } catch (\Throwable $e) {
                        return StepResult::failure('Download failed: ' . $e->getMessage());
                    }
                }
            }
        }

        return StepResult::success();
    }
}
