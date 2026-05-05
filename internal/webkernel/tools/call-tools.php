<?php declare(strict_types=1);
/**
 * Webkernel Tool Runner
 *
 * Usage:
 *   php call-tools.php <category>/<tool> [args]   --with-autoload --with-fastboot
 *   php call-tools.php <category>/all   [args]
 *   php call-tools.php <artisan:command> [args]
 *
 * Programmatic (autoload already done):
 *   WebkernelToolRunner::create()->run('cache/clear');
 *
 * @package webkernel/webkernel
 * @license EPL-2.0
 */

if (!class_exists('WebkernelToolRunner', false)) {

final class WebkernelToolRunner
{
    /** @var array<string, callable> */
    private array $tools = [];

    private string $root;
    private string $toolsDir;

    private function __construct(string $root, string $toolsDir)
    {
        $this->root     = $root;
        $this->toolsDir = $toolsDir;

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', $root);
        }
    }

    public static function create(string $root = null, string $toolsDir = null): self
        {
            return new self(
                $root     ?? (defined('BASE_PATH') ? BASE_PATH : dirname(__FILE__, 4)),
                $toolsDir ?? __DIR__,
            );
        }

    // -- Bootstrap ------------------------------------------------------------

    public static function loadAutoload(): void
    {
        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__FILE__, 4);
        require_once $root . '/packages/autoload.php';
    }

    public static function loadFastBoot(): void
    {
        defined('BASE_PATH') || require_once dirname(__DIR__) . '/fast-boot.php';
    }

    // -- Registry -------------------------------------------------------------

    /**
     * Register a tool callable by key.
     *
     * @param string   $key   The tool key (e.g. 'cache/clear')
     * @param callable $fn    The tool callable
     * @return self
     */
    public function register(string $key, callable $fn): self
    {
        $this->tools[$key] = $fn;
        return $this;
    }

    /** @param array<string, callable> $tools */
    public function registerMany(array $tools): self
    {
        foreach ($tools as $k => $fn) {
            $this->tools[$k] = $fn;
        }
        return $this;
    }

    /** @return array<string, callable> */
    public function getTools(): array { return $this->tools; }
    public function getRoot(): string { return $this->root; }

    // -- Dispatch -------------------------------------------------------------

    private function log(string $level, string $message): void
    {
        $bg = match ($level) {
            'INFO'  => "\e[44m",
            'WARN'  => "\e[43m\e[30m",
            'ERROR' => "\e[41m",
            'FAIL'  => "\e[41m",
            'DONE'  => "\e[42m\e[30m",
            default => "\e[44m",
        };
        $reset = "\e[0m";
        $text  = ($level === 'WARN' || $level === 'DONE') ? "" : "\e[97m";
        
        if ($level === 'INFO') {
            fwrite(STDOUT, PHP_EOL);
        }
        
        fwrite(STDOUT, sprintf("%s%s CALL-TOOLS %s %s" . PHP_EOL, $bg, $text, $reset, $message));

        if ($level === 'DONE' || $level === 'ERROR' || $level === 'FAIL') {
            fwrite(STDOUT, PHP_EOL);
        }
    }

    /**
     * Run a tool or artisan command.
     *
     * @param string $command The tool or artisan command to run
     * @param array  $args    The command arguments
     * @return int Exit code
     */
    public function run(string $command, array $args = []): int
    {
        if ($command === '') return 0;
        return str_contains($command, '/') ? $this->tool($command, $args) : $this->artisan($command, $args);
    }

    /**
     * Run a tool by key.
     *
     * @param string $command The tool key (e.g. 'cache/clear')
     * @param array  $args    The command arguments
     * @return int Exit code
     */
    private function tool(string $command, array $args): int
    {
        if (isset($this->tools[$command])) {
            $this->log('INFO', "Running registered tool [{$command}]");
            ($this->tools[$command])($args);
            return 0;
        }

        [$cat, $name] = explode('/', $command, 2);

        if ($cat !== 'installer' && (!file_exists($this->root . '/internal/app.php') || !file_exists($this->root . '/.env'))) {
            $this->log('WARN', "Skipping tool [{$command}] (missing environment)");
            return 0;
        }

        $dir = $this->toolsDir . '/' . $cat;

        if ($name === 'all') {
            $files = glob($dir . '/*.php') ?: [];
            sort($files);
            $this->log('INFO', "Running all tools in category [{$cat}] (" . count($files) . " found)");
            foreach ($files as $f) $this->exec($f, $args);
            return 0;
        }

        $file = $dir . '/' . $name . '.php';
        if (file_exists($file)) {
            $this->log('INFO', "Executing tool [{$command}]");
            $this->exec($file, $args);
            $this->log('DONE', "Tool [{$command}] completed.");
            return 0;
        }

        $this->log('WARN', "Tool [{$command}] not found, falling back to Artisan...");
        return $this->artisan($command, $args);
    }

    /**
     * Run an artisan command.
     *
     * @param string $command The artisan command to run
     * @param array  $args    The command arguments
     * @return int Exit code
     */
    public function artisan(string $command, array $args = []): int
    {
        $artisan = $this->root . '/artisan';
        if (!file_exists($artisan)) {
            fwrite(STDERR, '[call-tools] artisan not found: ' . $artisan . PHP_EOL);
            return 1;
        }
        return self::proc(PHP_BINARY, $artisan, $command, $args);
    }

    /**
     * Run a PHP script using proc_open.
     *
     * @param string $file  The script file to run
     * @param array  $args  The script arguments
     * @return int Exit code
     */
    private function exec(string $file, array $args): void
    {
        $GLOBALS['argc'] = count($args) + 1;
        $GLOBALS['argv'] = [PHP_BINARY, ...$args];
        (static function (string $_f, array $_a, WebkernelToolRunner $_r): void {
            $args = $_a; $runner = $_r;
            include $_f;
        })($file, $args, $this);
    }

    // -- proc_open (no passthru / exec / shell_exec) --------------------------

    /**
     * Run a process using proc_open.
     *
     * @param string $bin     The binary to run
     * @param string $script  The script to run (optional)
     * @param string $command The command to run (optional)
     * @param array  $extra   Extra arguments to pass to the process
     * @return int Exit code
     */
    public static function proc(string $bin, string $script, string $command, array $extra = []): int
    {
        $argv = array_values(array_filter(
            [$bin, ...($script !== '' ? [$script] : []), ...($command !== '' ? [$command] : []), ...$extra, '--ansi'],
            static fn (string $v) => $v !== '',
        ));

        $process = proc_open($argv, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes);
        if (!is_resource($process)) {
            fwrite(STDERR, '[call-tools] proc_open failed: ' . $command . PHP_EOL);
            return 1;
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $status = proc_get_status($process);
            ($o = stream_get_contents($pipes[1])) !== '' && $o !== false && print($o);
            ($e = stream_get_contents($pipes[2])) !== '' && $e !== false && fwrite(STDERR, $e);
            if (!$status['running']) break;
            usleep(50000);
        }

        ($o = stream_get_contents($pipes[1])) !== '' && $o !== false && print($o);
        ($e = stream_get_contents($pipes[2])) !== '' && $e !== false && fwrite(STDERR, $e);

        fclose($pipes[1]);
        fclose($pipes[2]);
        return (int) proc_close($process);
    }
}

} // class_exists

// -- CLI / Composer entry point -----------------------------------------------

if (isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {

    $withAutoload = false;
    $withFastBoot = false;
    $filtered     = [];

    foreach (array_slice($argv, 1) as $t) {
        match ($t) {
            '--with-autoload' => $withAutoload = true,
            '--with-fastboot' => $withFastBoot = true,
            default           => $filtered[] = $t,
        };
    }

    $withAutoload && WebkernelToolRunner::loadAutoload();
    $withFastBoot && WebkernelToolRunner::loadFastBoot();

    $tools_array = [];

    $command = $filtered[0] ?? null;
    if ($command === null) exit(0);

    exit(WebkernelToolRunner::create()->registerMany($tools_array)->run($command, array_slice($filtered, 1)));
}
