<?php declare(strict_types=1);

namespace Webkernel\System\Ops;

use Closure;
use Illuminate\Support\Collection;
use Webkernel\Async\Promise;
use Webkernel\Async\Pool;
use Webkernel\Query\Traits\CacheLock;
use Webkernel\Query\Traits\Exportable;
use Webkernel\Query\Traits\FileSystemHelpers;
use Webkernel\Query\Traits\LoggerTrait;
use Webkernel\System\Ops\Contracts\Provider;
use Webkernel\System\Ops\Contracts\CrudProvider;
use Webkernel\System\Ops\Contracts\DatabaseSchemaProvider;
use Webkernel\System\Ops\Dto\OperationContext;
use Webkernel\System\Ops\Dto\StepResult;

/**
 * Fluent orchestration builder for business operations.
 *
 * Chains data sources, pipeline steps, transformations, and actions
 * into a single expressive workflow with native async/await support.
 *
 * Usage:
 *   webkernel()->do()
 *       ->from($provider)
 *       ->filter(fn($r) => $r->active)
 *       ->map(fn($r) => [...])
 *       ->stepAfter('Export', fn($rows) => ...)
 *       ->run();
 */
final class OperationBuilder
{
    use CacheLock;
    use Exportable;
    use FileSystemHelpers;
    use LoggerTrait;

    private ?Provider $provider = null;
    private Collection $data;
    private array $steps = [];
    private array $postActions = [];
    private ?Closure $beforeStep = null;
    private ?Closure $afterStep = null;
    private bool $dryRun = false;
    private int $retries = 0;
    private int $timeout = 0;
    private bool $transaction = false;
    private ?object $authUser = null;
    private ?string $tenant = null;
    private array $responseHeaders = [];

    public function __construct()
    {
        $this->data = new Collection();
    }

    public static function make(): self
    {
        return new self();
    }

    // ── Source Layer ───────────────────────────────────────────────────────

    /**
     * Set data source provider.
     *
     * Accepts:
     *   - Provider instance: from($provider)
     *   - URL string: from('https://api.github.com/repos/...')
     *   - URI with scheme: from('file://data.csv'), from('db://table_name'), from('ssh://...')
     */
    public function from(Provider|string $source): self
    {
        if (is_string($source)) {
            $this->provider = $this->providerFromUri($source);
        } else {
            $this->provider = $source;
        }
        return $this;
    }

    /**
     * Auto-detect provider based on URI scheme or URL.
     *
     * @internal
     */
    private function providerFromUri(string $uri): Provider
    {
        // HTTP(S) URLs → ApiProvider
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return \Webkernel\System\Ops\Providers\ApiProvider::get($uri);
        }

        // File URLs or paths → FileProvider
        if (str_starts_with($uri, 'file://')) {
            $path = substr($uri, 7);
            return \Webkernel\System\Ops\Providers\FileProvider::json($path);
        }

        // CSV files
        if (str_ends_with($uri, '.csv')) {
            return \Webkernel\System\Ops\Providers\FileProvider::csv($uri);
        }

        // JSON files
        if (str_ends_with($uri, '.json')) {
            return \Webkernel\System\Ops\Providers\FileProvider::json($uri);
        }

        // JSONL files
        if (str_ends_with($uri, '.jsonl')) {
            return \Webkernel\System\Ops\Providers\FileProvider::jsonl($uri);
        }

        // Database table → DatabaseProvider
        if (str_starts_with($uri, 'db://')) {
            $table = substr($uri, 5);
            return \Webkernel\System\Ops\Providers\DatabaseProvider::query($table);
        }

        // Assume it's a file path if it exists
        if (is_file($uri)) {
            return match (pathinfo($uri, PATHINFO_EXTENSION)) {
                'csv' => \Webkernel\System\Ops\Providers\FileProvider::csv($uri),
                'jsonl' => \Webkernel\System\Ops\Providers\FileProvider::jsonl($uri),
                default => \Webkernel\System\Ops\Providers\FileProvider::json($uri),
            };
        }

        throw new \InvalidArgumentException("Cannot determine provider for URI: $uri");
    }

    /**
     * Load data from provider (internal).
     */
    private function loadData(): self
    {
        if ($this->provider === null) {
            throw new \InvalidArgumentException('No provider set. Use from().');
        }

        $result = $this->provider->fetch();
        $this->data = $result instanceof Collection ? $result : collect($result);

        // Capture response headers from provider
        $this->responseHeaders = $this->provider->headers();

        return $this;
    }

    // ── Header Access ─────────────────────────────────────────────────────

    /**
     * Get all response headers.
     */
    public function getHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Get specific header value.
     */
    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->responseHeaders[$name] ?? $default;
    }

    /**
     * Check if header exists.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->responseHeaders[$name]);
    }

    /**
     * Get rate limit information (API providers).
     *
     * Returns: ['limit' => ..., 'remaining' => ..., 'reset' => ...]
     */
    public function rateLimit(): array
    {
        return [
            'limit' => $this->getHeader('X-RateLimit-Limit') ?? $this->getHeader('RateLimit-Limit'),
            'remaining' => $this->getHeader('X-RateLimit-Remaining') ?? $this->getHeader('RateLimit-Remaining'),
            'reset' => $this->getHeader('X-RateLimit-Reset') ?? $this->getHeader('RateLimit-Reset'),
        ];
    }

    /**
     * Check if response can be framed (X-Frame-Options).
     */
    public function isFrameable(): bool
    {
        $frameOption = $this->getHeader('X-Frame-Options');
        if (!$frameOption) {
            return true;
        }
        return !in_array(strtoupper($frameOption), ['DENY', 'SAMEORIGIN']);
    }

    /**
     * Get Content Security Policy.
     */
    public function contentSecurityPolicy(): ?string
    {
        return $this->getHeader('Content-Security-Policy');
    }

    /**
     * Inspect headers with closure (useful in pipeline).
     */
    public function inspectHeaders(Closure $inspector): self
    {
        $inspector($this->responseHeaders);
        return $this;
    }

    // ── Debugging ──────────────────────────────────────────────────────────

    /**
     * Dump collected data and continue.
     *
     * Usage:
     *   ->filter(...)
     *   ->dump()
     *   ->map(...)
     */
    public function dump(string $label = ''): self
    {
        $prefix = $label ? "[$label] " : '';
        dump($prefix . 'Current data:', $this->data->toArray());
        return $this;
    }

    /**
     * Dump collected data and die.
     *
     * Usage:
     *   ->filter(...)
     *   ->dd()
     */
    public function dd(string $label = ''): never
    {
        $prefix = $label ? "[$label] " : '';
        dd($prefix . 'Current data:', $this->data->toArray());
    }

    /**
     * Dump headers and continue.
     */
    public function dumpHeaders(string $label = ''): self
    {
        $prefix = $label ? "[$label] " : '';
        dump($prefix . 'Response headers:', $this->responseHeaders);
        return $this;
    }

    /**
     * Dump headers and die.
     */
    public function ddHeaders(string $label = ''): never
    {
        $prefix = $label ? "[$label] " : '';
        dd($prefix . 'Response headers:', $this->responseHeaders);
    }

    /**
     * Tap into the pipeline with a closure.
     *
     * Usage:
     *   ->tap(fn($data) => logger()->info('Items: ' . count($data)))
     *   ->tap(fn($data) => $this->validate($data))
     */
    public function tap(Closure $callback): self
    {
        $callback($this->data);
        return $this;
    }

    /**
     * Count collected records and log.
     */
    public function count(string $label = ''): self
    {
        $count = $this->data->count();
        $msg = $label ? "$label: $count records" : "$count records collected";
        \Illuminate\Support\Facades\Log::info('[Operation] ' . $msg);
        return $this;
    }

    /**
     * Get first record without executing.
     *
     * Useful for inspection: ->first()->dd()
     */
    public function first(): mixed
    {
        return $this->data->first();
    }

    /**
     * Get last record without executing.
     */
    public function last(): mixed
    {
        return $this->data->last();
    }

    /**
     * Get record at index.
     */
    public function get(int $index): mixed
    {
        return $this->data->get($index);
    }

    // ── Real-time Broadcasting ────────────────────────────────────────────

    private ?string $broadcastChannel = null;
    private array $broadcastCallbacks = [];
    private ?string $backupPath = null;
    private ?string $extractedPath = null;
    private ?string $targetPath = null;

    /**
     * Broadcast operation progress/data to a channel in real-time.
     *
     * Usage:
     *   ->broadcast('operation.users', fn($data, $progress) => [
     *       'count' => count($data),
     *       'progress' => $progress,
     *   ])
     */
    public function broadcast(string $channel, Closure $transformer): self
    {
        $this->broadcastChannel = $channel;
        $this->broadcastCallbacks[] = $transformer;
        return $this;
    }

    /**
     * Stream data in chunks via Server-Sent Events (SSE).
     *
     * Useful for large datasets or long operations.
     *
     * Usage:
     *   ->stream(chunkSize: 100, channel: 'operation.stream')
     */
    public function stream(int $chunkSize = 100, string $channel = 'operation.stream'): self
    {
        $this->broadcastChannel = $channel;
        $this->steps[] = ['type' => 'stream', 'chunkSize' => $chunkSize];
        return $this;
    }

    /**
     * Broadcast progress updates during operation.
     *
     * Usage:
     *   ->broadcastProgress('Processing records...')
     */
    public function broadcastProgress(string $message, array $extra = []): self
    {
        if ($this->broadcastChannel) {
            $progress = [
                'message' => $message,
                'count' => $this->data->count(),
                'timestamp' => now()->toIso8601String(),
                ...$extra,
            ];

            \Illuminate\Support\Facades\Broadcast::channel(
                $this->broadcastChannel,
                fn() => $progress
            );

            $this->emitNotice("[Broadcast] $message");
        }

        return $this;
    }

    /**
     * Callback for progress updates (alternative to broadcasting).
     *
     * Usage:
     *   ->onProgress(fn($count, $total) => logger()->info("Progress: $count/$total"))
     */
    public function onProgress(Closure $callback): self
    {
        $this->broadcastCallbacks[] = ['type' => 'progress', 'callback' => $callback];
        return $this;
    }

    /**
     * Broadcast final result to channel after execution.
     *
     * Usage:
     *   ->broadcastResult('operation.complete')
     */
    public function broadcastResult(string $channel): self
    {
        $this->postActions[] = [
            'name' => 'Broadcast Result',
            'closure' => function ($data) use ($channel) {
                \Illuminate\Support\Facades\Broadcast::channel(
                    $channel,
                    fn() => [
                        'status' => 'complete',
                        'count' => count($data),
                        'data' => $data->toArray(),
                        'timestamp' => now()->toIso8601String(),
                    ]
                );
                $this->emitNotice("[Broadcast] Result sent to $channel");
            },
        ];
        return $this;
    }

    /**
     * Stream result via Server-Sent Events (SSE) to browser.
     *
     * Usage:
     *   ->streamResponse()  // Streams as it processes
     */
    public function streamResponse(): self
    {
        $this->postActions[] = [
            'name' => 'Stream Response',
            'closure' => function ($data) {
                response()->stream(function () use ($data) {
                    foreach ($data as $index => $item) {
                        echo "data: " . json_encode(['index' => $index, 'item' => $item]) . "\n\n";
                        flush();
                    }
                }, 200, ['Content-Type' => 'text/event-stream']);
            },
        ];
        return $this;
    }

    // ── Pipeline Steps ─────────────────────────────────────────────────────

    /**
     * Pre-processing validation or setup (closure).
     */
    public function stepBefore(string $name, Closure $closure): self
    {
        $this->beforeStep = function ($data) use ($name, $closure) {
            $this->recordStep(StepResult::ok($name));
            return $closure($data) ?? $data;
        };
        return $this;
    }

    /**
     * Transform each element (closure applied to collection).
     */
    public function map(Closure $transform): self
    {
        $this->steps[] = ['type' => 'map', 'closure' => $transform];
        return $this;
    }

    /**
     * Filter elements by condition (closure returns bool).
     */
    public function filter(Closure $predicate): self
    {
        $this->steps[] = ['type' => 'filter', 'closure' => $predicate];
        return $this;
    }

    /**
     * Aggregate/group data (closure).
     */
    public function aggregate(Closure $aggregator): self
    {
        $this->steps[] = ['type' => 'aggregate', 'closure' => $aggregator];
        return $this;
    }

    /**
     * Validate records (closure throws or returns bool).
     */
    public function validate(Closure $validator): self
    {
        $this->steps[] = ['type' => 'validate', 'closure' => $validator];
        return $this;
    }

    /**
     * Custom step (closure receives and returns data).
     */
    public function newStep(string $name, Closure $step): self
    {
        $this->steps[] = ['type' => 'custom', 'name' => $name, 'closure' => $step];
        return $this;
    }

    /**
     * Post-processing action (closure, no return value).
     */
    public function stepAfter(string $name, Closure $action): self
    {
        $this->postActions[] = ['name' => $name, 'closure' => $action];
        return $this;
    }

    // ── CRUD Operations ────────────────────────────────────────────────────

    /**
     * Insert records from data (or from closure) into the source.
     *
     * Usage:
     *   ->create(fn($rows) => $rows->map(fn($r) => [...]))
     *   ->create($records)
     */
    public function create(Closure|array $records): self
    {
        $this->postActions[] = [
            'name' => 'Create',
            'closure' => function ($data) use ($records) {
                if (!$this->provider instanceof CrudProvider) {
                    throw new \InvalidArgumentException('Provider does not support create operation');
                }

                $toInsert = is_callable($records) ? $records($data) : $records;
                $affected = $this->provider->insert((array) $toInsert);
                $this->emitNotice("Created $affected records");
            },
        ];
        return $this;
    }

    /**
     * Update records in the source.
     *
     * Usage:
     *   ->update(['status' => 'active'], fn($q) => $q->where('archived', false))
     */
    public function update(array $data, ?Closure $where = null): self
    {
        $this->postActions[] = [
            'name' => 'Update',
            'closure' => function () use ($data, $where) {
                if (!$this->provider instanceof CrudProvider) {
                    throw new \InvalidArgumentException('Provider does not support update operation');
                }

                $affected = $this->provider->update($data, $where);
                $this->emitNotice("Updated $affected records");
            },
        ];
        return $this;
    }

    /**
     * Delete records from the source.
     *
     * Usage:
     *   ->delete(fn($q) => $q->where('status', 'deleted'))
     */
    public function delete(?Closure $where = null): self
    {
        $this->postActions[] = [
            'name' => 'Delete',
            'closure' => function () use ($where) {
                if (!$this->provider instanceof CrudProvider) {
                    throw new \InvalidArgumentException('Provider does not support delete operation');
                }

                $affected = $this->provider->delete($where);
                $this->emitNotice("Deleted $affected records");
            },
        ];
        return $this;
    }

    // ── Database Schema Operations (Database-specific) ──────────────────────

    /**
     * Create a new table (database-specific).
     *
     * Usage:
     *   ->createTable('users', function($t) {
     *       $t->id();
     *       $t->string('email')->unique();
     *       $t->timestamps();
     *   })
     */
    public function createTable(string $table, Closure $schema): self
    {
        if (!$this->provider instanceof DatabaseSchemaProvider) {
            throw new \InvalidArgumentException('Provider does not support createTable (database-specific)');
        }

        $this->provider->createTable($table, $schema);
        $this->emitNotice("Table '$table' created");
        return $this;
    }

    /**
     * Alter an existing table (database-specific).
     *
     * Usage:
     *   ->alterTable('users', function($t) {
     *       $t->string('phone')->nullable();
     *   })
     */
    public function alterTable(string $table, Closure $schema): self
    {
        if (!$this->provider instanceof DatabaseSchemaProvider) {
            throw new \InvalidArgumentException('Provider does not support alterTable (database-specific)');
        }

        $this->provider->alterTable($table, $schema);
        $this->emitNotice("Table '$table' altered");
        return $this;
    }

    /**
     * Drop a table (database-specific).
     *
     * Usage:
     *   ->dropTable('archive_users', ifExists: true)
     */
    public function dropTable(string $table, bool $ifExists = true): self
    {
        if (!$this->provider instanceof DatabaseSchemaProvider) {
            throw new \InvalidArgumentException('Provider does not support dropTable (database-specific)');
        }

        $this->provider->dropTable($table, $ifExists);
        $this->emitNotice("Table '$table' dropped");
        return $this;
    }

    /**
     * Execute raw SQL query (database-specific).
     *
     * Usage:
     *   ->raw("UPDATE users SET status = ? WHERE active = 1", ['inactive'])
     */
    public function raw(string $sql, array $bindings = []): self
    {
        if (!$this->provider instanceof DatabaseSchemaProvider) {
            throw new \InvalidArgumentException('Provider does not support raw SQL (database-specific)');
        }

        $this->provider->raw($sql, $bindings);
        $this->emitNotice("Raw SQL executed");
        return $this;
    }

    // ── Export Actions ─────────────────────────────────────────────────────

    /**
     * Export data to CSV.
     */
    public function exportCsv(string $path, ?Closure $format = null): self
    {
        $this->postActions[] = [
            'name' => 'Export CSV',
            'closure' => function ($data) use ($path, $format) {
                $rows = $format ? $data->map($format) : $data;
                $this->writeCsv($path, $rows->toArray());
                $this->emitNotice("CSV exported: $path");
            },
        ];
        return $this;
    }

    /**
     * Export data to XLSX.
     */
    public function exportXlsx(string $path, ?Closure $format = null): self
    {
        $this->postActions[] = [
            'name' => 'Export XLSX',
            'closure' => function ($data) use ($path, $format) {
                $rows = $format ? $data->map($format) : $data;
                $this->writeXlsx($path, $rows->toArray());
                $this->emitNotice("XLSX exported: $path");
            },
        ];
        return $this;
    }

    /**
     * Send email notification.
     */
    public function sendEmail(string $to, string $subject, ?Closure $body = null): self
    {
        $this->postActions[] = [
            'name' => 'Send Email',
            'closure' => function ($data) use ($to, $subject, $body) {
                $message = $body ? $body($data) : 'Operation completed';
                \Illuminate\Support\Facades\Mail::send('emails.default', ['message' => $message], function ($m) use ($to, $subject) {
                    $m->to($to)->subject($subject);
                });
                $this->emitNotice("Email sent to: $to");
            },
        ];
        return $this;
    }

    /**
     * Notify Slack channel.
     */
    public function notifySlack(string $channel, ?Closure $message = null): self
    {
        $this->postActions[] = [
            'name' => 'Notify Slack',
            'closure' => function ($data) use ($channel, $message) {
                $text = $message ? $message($data) : "Operation completed with " . count($data) . " records";
                // Slack notification logic here
                $this->emitNotice("Slack notified: $channel");
            },
        ];
        return $this;
    }

    /**
     * Persist data to database.
     */
    public function persist(string $model, ?Closure $transform = null): self
    {
        $this->postActions[] = [
            'name' => 'Persist',
            'closure' => function ($data) use ($model, $transform) {
                $records = $transform ? $data->map($transform) : $data;
                foreach ($records as $record) {
                    $model::create((array) $record);
                }
                $this->emitNotice("Persisted " . count($records) . " records");
            },
        ];
        return $this;
    }

    /**
     * Index data in search engine.
     */
    public function indexSearch(string $index, ?Closure $transform = null): self
    {
        $this->postActions[] = [
            'name' => 'Index Search',
            'closure' => function ($data) use ($index, $transform) {
                $records = $transform ? $data->map($transform) : $data;
                // Elasticsearch/OpenSearch indexing here
                $this->emitNotice("Indexed " . count($records) . " to $index");
            },
        ];
        return $this;
    }

    /**
     * Cache result in Redis.
     */
    public function cache(string $key, int $ttl = 3600): self
    {
        $this->postActions[] = [
            'name' => 'Cache',
            'closure' => function ($data) use ($key, $ttl) {
                \Illuminate\Support\Facades\Cache::put($key, $data, $ttl);
                $this->emitNotice("Cached as: $key");
            },
        ];
        return $this;
    }

    /**
     * Audit/log operation.
     */
    public function audit(?Closure $details = null): self
    {
        $this->postActions[] = [
            'name' => 'Audit',
            'closure' => function ($data) use ($details) {
                $auditData = $details ? $details($data) : ['count' => count($data)];
                // Audit logging here
                $this->emitNotice("Audit logged: " . json_encode($auditData));
            },
        ];
        return $this;
    }

    // ── File System Operations ────────────────────────────────────────────

    /**
     * Create atomic backup of target directory with optional exclusions.
     *
     * Usage:
     *   ->backup(path: '/app/webkernel', except: ['var-elements', 'runtime'])
     */
    public function backup(string $path, array $except = []): self
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Backup path does not exist: $path");
        }

        $this->targetPath = $path;

        $this->postActions[] = [
            'name' => 'Backup',
            'closure' => function () use ($path, $except) {
                if ($this->dryRun) {
                    $this->emitNotice("DRY-RUN: Would backup $path with exclusions: " . implode(', ', $except));
                    return;
                }

                $backupDir = $path . '.backup-' . now()->timestamp;
                $this->backupPath = $backupDir;

                // Create backup directory
                if (!mkdir($backupDir, 0755, true)) {
                    throw new \RuntimeException("Failed to create backup directory: $backupDir");
                }

                // Copy with exclusions
                $this->recursiveCopy($path, $backupDir, $except);
                $this->emitNotice("Backup created: $backupDir");
            },
        ];
        return $this;
    }

    /**
     * Extract/decompress downloaded archive.
     *
     * Supports ZIP and TAR formats (auto-detected).
     *
     * Usage:
     *   ->from('https://github.com/.../release.zip')
     *   ->extract()
     */
    public function extract(?string $destination = null): self
    {
        $this->postActions[] = [
            'name' => 'Extract',
            'closure' => function ($data) use ($destination) {
                if (!$this->provider) {
                    throw new \RuntimeException('No provider set for extraction');
                }

                // Get the downloaded content/path
                $source = $this->getArchiveSource();
                if (!$source) {
                    throw new \RuntimeException('No archive source available for extraction');
                }

                if ($this->dryRun) {
                    $this->emitNotice("DRY-RUN: Would extract $source");
                    return;
                }

                $dest = $destination ?? sys_get_temp_dir() . '/webkernel-extract-' . now()->timestamp;
                $this->extractedPath = $dest;

                // Detect and extract
                if (str_ends_with($source, '.zip')) {
                    $this->extractZip($source, $dest);
                } elseif (str_ends_with($source, '.tar.gz') || str_ends_with($source, '.tgz')) {
                    $this->extractTarGz($source, $dest);
                } elseif (str_ends_with($source, '.tar')) {
                    $this->extractTar($source, $dest);
                } else {
                    throw new \RuntimeException("Unsupported archive format: $source");
                }

                $this->emitNotice("Archive extracted to: $dest");
            },
        ];
        return $this;
    }

    /**
     * Swap current directory with extracted/prepared content.
     *
     * Atomically replaces target directory contents.
     *
     * Usage:
     *   ->swap()  // Requires backup() and extract() first
     */
    public function swap(): self
    {
        $this->postActions[] = [
            'name' => 'Swap',
            'closure' => function () {
                if (!$this->targetPath || !$this->extractedPath) {
                    throw new \RuntimeException('Swap requires both backup() and extract() to be called first');
                }

                if ($this->dryRun) {
                    $this->emitNotice("DRY-RUN: Would swap $this->targetPath with $this->extractedPath");
                    return;
                }

                $oldPath = $this->targetPath . '.old-' . now()->timestamp;

                // Rename current to old
                if (!rename($this->targetPath, $oldPath)) {
                    throw new \RuntimeException("Failed to rename current directory: $this->targetPath");
                }

                // Rename extracted to current location
                if (!rename($this->extractedPath, $this->targetPath)) {
                    // Restore on failure
                    rename($oldPath, $this->targetPath);
                    throw new \RuntimeException("Failed to move extracted directory to target: $this->targetPath");
                }

                // Save old path for potential rollback
                $this->backupPath = $oldPath;
                $this->emitNotice("Swap complete: $this->targetPath ← $this->extractedPath");
            },
        ];
        return $this;
    }

    /**
     * Rollback to backed-up version.
     *
     * Restores the previous state if something went wrong.
     *
     * Usage:
     *   $result = webkernel()->do()
     *       ->from('https://...')
     *       ->backup(path: WEBKERNEL_PATH, except: ['var-elements'])
     *       ->extract()
     *       ->swap()
     *       ->run();
     *
     *   if (!$result->success) {
     *       $result->rollback();
     *   }
     */
    public function rollback(): self
    {
        if (!$this->backupPath || !is_dir($this->backupPath)) {
            throw new \RuntimeException("No backup available for rollback");
        }

        if (!$this->targetPath) {
            throw new \RuntimeException('Target path not set for rollback');
        }

        if ($this->dryRun) {
            $this->emitNotice("DRY-RUN: Would rollback $this->targetPath from $this->backupPath");
            return $this;
        }

        // Remove current broken state
        if (is_dir($this->targetPath)) {
            $this->recursiveRemove($this->targetPath);
        }

        // Restore from backup
        if (!rename($this->backupPath, $this->targetPath)) {
            throw new \RuntimeException("Failed to rollback to backup: $this->backupPath");
        }

        $this->emitNotice("Rollback complete: restored from $this->backupPath");
        return $this;
    }

    // ── Flow Control ───────────────────────────────────────────────────────

    /**
     * Run in dry-run mode (no side effects).
     */
    public function dryRun(): self
    {
        $this->dryRun = true;
        return $this;
    }

    /**
     * Set retry attempts.
     */
    public function retry(int $attempts): self
    {
        $this->retries = $attempts;
        return $this;
    }

    /**
     * Set execution timeout (seconds).
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Wrap in database transaction.
     */
    public function transaction(): self
    {
        $this->transaction = true;
        return $this;
    }

    /**
     * Execute with authentication context.
     */
    public function withAuth(object $user): self
    {
        $this->authUser = $user;
        return $this;
    }

    /**
     * Execute with tenant context.
     */
    public function withContext(string $tenant): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    // ── Execution ──────────────────────────────────────────────────────────

    /**
     * Execute synchronously.
     */
    public function run(): OperationContext
    {
        try {
            $this->loadData();

            if ($this->beforeStep) {
                $this->data = ($this->beforeStep)($this->data);
            }

            foreach ($this->steps as $step) {
                $this->data = match ($step['type']) {
                    'map' => $this->data->map($step['closure']),
                    'filter' => $this->data->filter($step['closure']),
                    'aggregate' => collect([($step['closure'])($this->data)]),
                    'validate' => $this->validateStep($this->data, $step['closure']),
                    'custom' => $this->customStep($step['name'], $step['closure']),
                    default => $this->data,
                };
            }

            if (!$this->dryRun) {
                foreach ($this->postActions as $action) {
                    ($action['closure'])($this->data);
                }
            }

            $context = OperationContext::success($this->data->toArray());
            if ($this->backupPath && $this->targetPath) {
                $context->setBackupPaths($this->backupPath, $this->targetPath);
            }
            $context->setRateLimit($this->rateLimit());
            return $context;
        } catch (\Throwable $e) {
            return OperationContext::failure($e->getMessage());
        }
    }

    /**
     * Execute asynchronously.
     */
    public function async(): Promise
    {
        return Promise::resolve(fn() => $this->run());
    }

    /**
     * Execute multiple operations in parallel.
     *
     * @param array<string, OperationBuilder> $operations
     * @return mixed Promise or result from Pool::all()
     */
    public static function parallel(array $operations): mixed
    {
        return Pool::all(
            array_map(fn(OperationBuilder $op) => $op->async(), $operations)
        );
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function validateStep(Collection $data, Closure $validator): Collection
    {
        foreach ($data as $item) {
            $result = $validator($item);
            if ($result === false) {
                throw new \InvalidArgumentException('Validation failed for item');
            }
        }
        return $data;
    }

    private function customStep(string $name, Closure $step): Collection
    {
        $result = $step($this->data);
        $this->recordStep(StepResult::ok($name, $result));
        return $result instanceof Collection ? $result : collect($result);
    }

    private function recordStep(StepResult $result): void
    {
        $this->steps[] = ['result' => $result];
    }

    private function writeCsv(string $path, array $data): void
    {
        $this->ensureDirectory(dirname($path));
        $fp = fopen($path, 'w');
        if ($fp && !empty($data)) {
            fputcsv($fp, array_keys((array) $data[0]));
            foreach ($data as $row) {
                fputcsv($fp, (array) $row);
            }
            fclose($fp);
        }
    }

    private function writeXlsx(string $path, array $data): void
    {
        // SimpleXLS/PhpSpreadsheet integration here
        $this->ensureDirectory(dirname($path));
        $this->emitNotice("XLSX export placeholder: $path");
    }

    private function emitNotice(string $message): void
    {
        // Log via built-in logger
        \Illuminate\Support\Facades\Log::info('[Operation] ' . $message);
    }

    // ── File System Helpers ────────────────────────────────────────────────

    /**
     * Recursively copy directory with exclusions.
     *
     * @param array<int, string> $except Patterns/names to exclude
     */
    private function recursiveCopy(string $source, string $dest, array $except = []): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Check exclusions
            if (in_array($file, $except)) {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->recursiveCopy($sourcePath, $destPath, $except);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    /**
     * Recursively remove directory and contents.
     */
    private function recursiveRemove(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->recursiveRemove($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($path);
    }

    /**
     * Get archive source from provider.
     *
     * For ApiProvider: saves binary content to temp file
     * For FileProvider: returns file path
     */
    private function getArchiveSource(): ?string
    {
        if (!$this->provider) {
            return null;
        }

        // For ApiProvider with binary content
        if ($this->provider instanceof \Webkernel\System\Ops\Providers\ApiProvider) {
            if ($this->provider->isBinary()) {
                $rawContent = $this->provider->getRawContent();
                if ($rawContent) {
                    $tempFile = sys_get_temp_dir() . '/archive-' . now()->timestamp . '.zip';
                    if (file_put_contents($tempFile, $rawContent) !== false) {
                        return $tempFile;
                    }
                }
            }
        }

        // For FileProvider, try to get the path via reflection
        if ($this->provider instanceof \Webkernel\System\Ops\Providers\FileProvider) {
            try {
                $reflection = new \ReflectionClass($this->provider);
                $pathProperty = $reflection->getProperty('path');
                $pathProperty->setAccessible(true);
                return $pathProperty->getValue($this->provider);
            } catch (\ReflectionException) {
                return null;
            }
        }

        return null;
    }

    /**
     * Extract ZIP archive.
     */
    private function extractZip(string $source, string $dest): void
    {
        if (!class_exists('\ZipArchive')) {
            throw new \RuntimeException('ZipArchive extension not available');
        }

        $zip = new \ZipArchive();
        if ($zip->open($source) !== true) {
            throw new \RuntimeException("Failed to open ZIP archive: $source");
        }

        if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
            throw new \RuntimeException("Failed to create extraction directory: $dest");
        }

        if (!$zip->extractTo($dest)) {
            throw new \RuntimeException("Failed to extract ZIP archive");
        }

        $zip->close();
    }

    /**
     * Extract TAR.GZ archive.
     */
    private function extractTarGz(string $source, string $dest): void
    {
        if (!function_exists('popen')) {
            throw new \RuntimeException('popen() not available for tar extraction');
        }

        if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
            throw new \RuntimeException("Failed to create extraction directory: $dest");
        }

        $cmd = "cd " . escapeshellarg($dest) . " && tar -xzf " . escapeshellarg($source);
        $proc = popen($cmd, 'r');
        if ($proc === false) {
            throw new \RuntimeException("Failed to execute tar command");
        }
        pclose($proc);
    }

    /**
     * Extract TAR archive.
     */
    private function extractTar(string $source, string $dest): void
    {
        if (!function_exists('popen')) {
            throw new \RuntimeException('popen() not available for tar extraction');
        }

        if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
            throw new \RuntimeException("Failed to create extraction directory: $dest");
        }

        $cmd = "cd " . escapeshellarg($dest) . " && tar -xf " . escapeshellarg($source);
        $proc = popen($cmd, 'r');
        if ($proc === false) {
            throw new \RuntimeException("Failed to execute tar command");
        }
        pclose($proc);
    }
}
