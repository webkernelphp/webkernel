<?php declare(strict_types=1);



// =============================================================================
//  § 7  SetupFlow
// =============================================================================
/**
 * Declarative first-run wizard.
 *
 * Depends on: HmacSigner, WebkernelRouter, EmergencyPageBuilder
 * Depends on: WEBKERNEL_BRAND_LOGO_LIGHT, WEBKERNEL_BRAND_LOGO_DARK
 *             (branding constants — must be defined before this file is loaded)
 *
 * -----------------------------------------------------------------------
 *  Three kinds of pre-conditions
 *
 *  fastPath(fn)         If fn() === true, return immediately.
 *                       Laravel boots normally. Wizard never shown.
 *
 *  guard(fn, msg, sev)  If fn() === false, render an error page
 *                       immediately and terminate.
 *                       Use for hard server pre-conditions
 *                       (PHP version, extensions, permissions...).
 *
 *  gap(fn)              Silent side-effect closure injected between
 *                       steps at run time (chmod, mkdir, log...).
 *                       Exceptions bubble up as step failures.
 * -----------------------------------------------------------------------
 *
 *  Two conditional helpers:
 *
 *  when(bool, fn)       Calls fn($this) only when the condition is true.
 *                       Returns $this so the chain continues.
 *                       Use to register optional guards, notices, steps,
 *                       or any other fluent call without breaking the chain.
 *
 *  notice(level, h, b)  Registers an informational band rendered at the
 *                       top of every setup page produced by this flow.
 *                       Delegates to EmergencyPageBuilder::notice() on
 *                       each builder instance created during ->run().
 *                       $level: 'info' | 'warning' | 'error'
 */
final class SetupFlow
{
    // ── Config ────────────────────────────────────────────────────
    private readonly string $basePath;
    private readonly string $tokenFile;
    private int             $tokenTtl = 86400; // 24 h

    // ── Scope ─────────────────────────────────────────────────────
    private string $scope = 'setup';

    // ── Preview page ──────────────────────────────────────────────
    private string $previewTitle   = 'First-run Setup Required';
    private string $previewMessage = '<b>This application has not been initialised yet.</b>'
                                   . ' The following actions will be performed on this server.'
                                   . ' Review them carefully, then click Proceed when ready.';

    // ── Complete page ─────────────────────────────────────────────
    private string $completeTitle       = 'Setup Complete';
    private string $completeMessage     = '<b>The environment has been initialised successfully.</b>'
                                        . ' Database migrations will run automatically on first boot.'
                                        . ' Click the button below to open the application.';
    private string $completeButtonLabel = 'Open Application';
    private string $redirectAfterComplete = '/';

    // ── Incomplete (error) page ───────────────────────────────────
    private string $incompleteTitle   = 'Setup Incomplete';
    private string $incompleteMessage = 'One or more setup steps did not complete successfully.'
                                     . ' Check server permissions and try again.';

    // ── Token ─────────────────────────────────────────────────────
    private HmacSigner $signer;
    private string     $token;

    // ── Fast-paths ────────────────────────────────────────────────
    /** @var list<\Closure():bool> */
    private array $fastPaths = [];

    // ── Guards ────────────────────────────────────────────────────
    /** @var list<array{check:\Closure():bool, message:string, severity:string}> */
    private array $guards = [];

    // ── Steps ─────────────────────────────────────────────────────
    /**
     * @var list<array{
     *   label:   string,
     *   closure: \Closure|null,
     *   pending: bool,
     *   gap:     \Closure|null,
     *   _pre:    bool,
     * }>
     */
    private array $steps = [];

    // ── Notices ───────────────────────────────────────────────────
    /**
     * Notices are passed to every EmergencyPageBuilder instance created
     * inside registerRoutes(). They appear on every setup page.
     *
     * @var list<array{level:string, heading:string, body:string}>
     */
    private array $notices = [];

    // ── Logo ──────────────────────────────────────────────────────
    private ?string $logoLight = null;
    private ?string $logoDark  = null;

    // ── Constructor ───────────────────────────────────────────────
    private function __construct(string $basePath)
    {
        $this->basePath  = rtrim($basePath, '/\\');
        $this->tokenFile = $this->basePath . '/.deployment_setup_token';

        $this->logoLight = WEBKERNEL_BRAND_LOGO_LIGHT;
        $this->logoDark  = WEBKERNEL_BRAND_LOGO_DARK;
    }

    public static function create(string $basePath): self
    {
        return new self($basePath);
    }

    // ── Fluent API ────────────────────────────────────────────────

    /**
     * Conditional helper.
     *
     * Calls $fn($this) only when $condition is true, then returns $this.
     * The callback must return void (it operates on $this by reference via
     * the fluent chain inside it, but the outer chain continues from the
     * value returned by when() itself).
     *
     * Usage:
     *   ->when($someFlag, static fn (SetupFlow $f) => $f->notice(...)->guard(...))
     *
     * @param bool             $condition
     * @param \Closure(self):mixed $fn
     */
    public function when(bool $condition, \Closure $fn): self
    {
        if ($condition) {
            $fn($this);
        }
        return $this;
    }

    /**
     * Register an informational notice rendered on every setup page.
     *
     * Notices are forwarded to each EmergencyPageBuilder created during
     * ->run() via EmergencyPageBuilder::notice().
     *
     * @param string $level   'info' | 'warning' | 'error'
     * @param string $heading Short bold label shown above the body.
     * @param string $body    Full explanation. Safe HTML is allowed.
     */
    public function notice(string $level, string $heading, string $body): self
    {
        $this->notices[] = [
            'level'   => $level,
            'heading' => $heading,
            'body'    => $body,
        ];
        return $this;
    }

    /**
     * Fast-path: if the closure returns true, the wizard is skipped
     * entirely and ->run() returns immediately (Laravel boots normally).
     *
     * @param \Closure():bool $check
     */
    public function fastPath(\Closure $check): self
    {
        $this->fastPaths[] = $check;
        return $this;
    }

    /**
     * Guard: if the closure returns false, render an error page and exit.
     *
     * @param \Closure():bool $check
     * @param string $message  Human-readable description of what failed.
     * @param string $severity EmergencyPageBuilder severity (default CRITICAL).
     */
    public function guard(\Closure $check, string $message = '', string $severity = 'CRITICAL'): self
    {
        $this->guards[] = [
            'check'    => $check,
            'message'  => $message,
            'severity' => $severity,
        ];
        return $this;
    }

    /**
     * Gap: a silent side-effect closure injected before the next step.
     *
     * @param \Closure():void $fn
     */
    public function gap(\Closure $fn): self
    {
        $idx = count($this->steps) - 1;
        if ($idx >= 0) {
            $this->steps[$idx]['gap'] = $fn;
        } else {
            $this->steps[] = [
                'label'   => '',
                'closure' => null,
                'pending' => false,
                'gap'     => $fn,
                '_pre'    => true,
            ];
        }
        return $this;
    }

    /**
     * Add a visible setup step with an execution closure.
     *
     * @param \Closure():(bool|string) $closure
     */
    public function step(string $label, \Closure $closure): self
    {
        $this->steps[] = [
            'label'   => $label,
            'closure' => $closure,
            'pending' => false,
            'gap'     => null,
            '_pre'    => false,
        ];
        return $this;
    }

    /**
     * Add a pending (display-only) step — shown grayed out on all pages.
     */
    public function pendingStep(string $label): self
    {
        $this->steps[] = [
            'label'   => $label,
            'closure' => null,
            'pending' => true,
            'gap'     => null,
            '_pre'    => false,
        ];
        return $this;
    }

    public function previewPage(string $title, string $message = ''): self
    {
        $this->previewTitle = $title;
        if ($message !== '') $this->previewMessage = $message;
        return $this;
    }

    public function completePage(
        string $title,
        string $message      = '',
        string $buttonLabel  = 'Open Application',
        string $redirectTo   = '',
    ): self {
        $this->completeTitle = $title;
        if ($message !== '')     $this->completeMessage      = $message;
        if ($buttonLabel !== '') $this->completeButtonLabel  = $buttonLabel;
        if ($redirectTo  !== '') $this->redirectAfterComplete = $redirectTo;
        return $this;
    }

    public function redirectThenTo(string $url): self
    {
        $this->redirectAfterComplete = $url;
        return $this;
    }

    public function incompletePage(string $title, string $message): self
    {
        $this->incompleteTitle   = $title;
        $this->incompleteMessage = $message;
        return $this;
    }

    public function tokenTtl(int $seconds): self
    {
        $this->tokenTtl = max(60, $seconds);
        return $this;
    }

    public function logo(?string $light = null, ?string $dark = null): self
    {
        $this->logoLight = $light;
        $this->logoDark  = $dark;
        return $this;
    }

    public function scopeTo(string $scope): self
    {
        $this->scope = trim($scope, '/');
        return $this;
    }

    // ── run() — the single dispatch entry-point ───────────────────
    public function run(): void
    {
        $uri       = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
        $wkBase    = '/__webkernel-app/';
        $scopeBase = $wkBase . $this->scope . '/';

        // ── 0. Non-scoped WebkernelRouter routes ──────────────────
        if (str_starts_with($uri, $wkBase) && ! str_starts_with($uri, $scopeBase)) {
            if (WebkernelRouter::dispatch()) {
                exit(0);
            }
            return;
        }

        // ── 1. Fast-paths ─────────────────────────────────────────
        if (! str_starts_with($uri, $scopeBase)) {
            foreach ($this->fastPaths as $fp) {
                if ($fp()) {
                    return;
                }
            }
        }

        // ── 2. Guards ─────────────────────────────────────────────
        $this->runGuards();

        // ── 3. Resolve token + register scoped routes ─────────────
        [$this->signer, $this->token] = $this->resolveToken();
        $this->registerRoutes();

        // ── 4. Dispatch ───────────────────────────────────────────
        if (WebkernelRouter::dispatch()) {
            exit(0);
        }

        // ── 5. Fallback redirect -> scoped preview page ────────────
        $previewUrl = WebkernelRouter::url($this->scope . '/{token}', ['token' => $this->token]);
        header('Location: ' . $previewUrl, true, 302);
        exit(0);
    }

    // ── Internal: guards ──────────────────────────────────────────
    private function runGuards(): void
    {
        foreach ($this->guards as $guard) {
            if (! ($guard['check'])()) {
                $msg = $guard['message'] !== ''
                    ? $guard['message']
                    : 'A required server condition is not satisfied.';

                $builder = EmergencyPageBuilder::create()
                    ->title('Setup Cannot Proceed')
                    ->message(
                        "A required server condition is not satisfied:\n\n"
                        . "  X  {$msg}\n\n"
                        . "Fix the server configuration and reload this page."
                    )
                    ->severity($guard['severity'])
                    ->code(500)
                    ->systemState('ENVIRONMENT ERROR')
                    ->footer('SERVER CONFIGURATION ERROR — SETUP BLOCKED')
                    ->addButton('Reload', '/');

                $this->applyLogo($builder);
                $this->applyNotices($builder);

                $builder->render();
            }
        }
    }

    // ── Internal: apply shared state to a builder instance ────────

    private function applyLogo(EmergencyPageBuilder $builder): void
    {
        if ($this->logoLight !== null || $this->logoDark !== null) {
            $builder->logo($this->logoLight, $this->logoDark);
        }
    }

    /**
     * Forward all registered notices to a builder instance.
     * Called on every builder created inside registerRoutes() and runGuards()
     * so notices appear on every page the flow can render.
     */
    private function applyNotices(EmergencyPageBuilder $builder): void
    {
        foreach ($this->notices as $n) {
            $builder->notice($n['level'], $n['heading'], $n['body']);
        }
    }

    // ── Route registration ────────────────────────────────────────
    private function registerRoutes(): void
    {
        $token     = $this->token;
        $tokenFile = $this->tokenFile;
        $self      = $this;

        // ── GET /__webkernel-app/setup/{token} ────────────────────
        WebkernelRouter::register(
            'setup/{token}',
            static function (array $params) use ($token, $self): never {
                if (! hash_equals($token, $params['token'])) {
                    SetupFlow::renderBadToken();
                }

                $runUrl       = WebkernelRouter::url('setup/{token}/run',  ['token' => $token]);
                $canonicalUrl = WebkernelRouter::url('setup/{token}',      ['token' => $token]);

                $builder = EmergencyPageBuilder::create()
                    ->title($self->previewTitle)
                    ->severity('SETUP')
                    ->code(200)
                    ->systemState('FIRST-RUN SETUP')
                    ->canonicalize($canonicalUrl)
                    ->footer('WEBKERNEL — REVIEW AND CONFIRM BEFORE PROCEEDING')
                    ->message($self->previewMessage);

                $self->applyLogo($builder);
                $self->applyNotices($builder);

                foreach ($self->steps as $step) {
                    if ($step['_pre'] ?? false) continue;
                    $builder->step($step['label'], pending: true);
                }

                $builder->submitStep('Proceed with Setup', $runUrl);
                $builder->render();
            },
        );

        // ── GET /__webkernel-app/setup/{token}/run ────────────────
        WebkernelRouter::register(
            'setup/{token}/run',
            static function (array $params) use ($token, $self): never {
                if (! hash_equals($token, $params['token'])) {
                    SetupFlow::renderBadToken();
                }

                $completeUrl  = WebkernelRouter::url('setup/{token}/complete', ['token' => $token]);
                $canonicalUrl = WebkernelRouter::url('setup/{token}/run',      ['token' => $token]);

                $builder = EmergencyPageBuilder::create()
                    ->title('Setting Up Your Environment')
                    ->severity('SETUP')
                    ->code(200)
                    ->systemState('SETUP IN PROGRESS')
                    ->canonicalize($canonicalUrl)
                    ->footer('WEBKERNEL — FIRST-RUN SETUP');

                $self->applyLogo($builder);
                $self->applyNotices($builder);

                $preGaps      = [];
                $visibleSteps = [];

                foreach ($self->steps as $step) {
                    if ($step['_pre'] ?? false) {
                        if ($step['gap'] !== null) {
                            $preGaps[] = $step['gap'];
                        }
                    } else {
                        $visibleSteps[] = $step;
                    }
                }

                $preFired        = false;
                $capturedPreGaps = $preGaps;

                foreach ($visibleSteps as $step) {
                    $closure = $step['closure'];
                    $gap     = $step['gap'];

                    if ($closure === null) {
                        $builder->step($step['label'], pending: true);
                        continue;
                    }

                    $wrapped = static function () use ($closure, $gap, $capturedPreGaps, &$preFired): bool|string {
                        if (! $preFired) {
                            $preFired = true;
                            foreach ($capturedPreGaps as $preGap) {
                                try { $preGap(); } catch (\Throwable) { /* swallowed */ }
                            }
                        }
                        $result = $closure();
                        if ($result === true && $gap !== null) {
                            try { $gap(); } catch (\Throwable) { /* swallowed */ }
                        }
                        return $result;
                    };

                    $builder->step($step['label'], $wrapped);
                }

                $builder->submitStep('Review Setup Result', $completeUrl);
                $builder->render();
            },
        );

        // ── GET /__webkernel-app/setup/{token}/complete ───────────
        WebkernelRouter::register(
            'setup/{token}/complete',
            static function (array $params) use ($token, $tokenFile, $self): never {
                if (! hash_equals($token, $params['token'])) {
                    SetupFlow::renderBadToken();
                }

                $ready = false;
                foreach ($self->fastPaths as $fp) {
                    if ($fp()) {
                        $ready = true;
                        break;
                    }
                }

                $canonicalUrl = WebkernelRouter::url('setup/{token}/complete', ['token' => $token]);

                if ($ready) {
                    @unlink($tokenFile);
                }

                $builder = EmergencyPageBuilder::create()
                    ->severity($ready ? 'SETUP' : 'WARNING')
                    ->code($ready ? 200 : 500)
                    ->systemState($ready ? 'SETUP COMPLETE' : 'SETUP INCOMPLETE')
                    ->canonicalize($canonicalUrl)
                    ->footer('WEBKERNEL — FIRST-RUN SETUP');

                $self->applyLogo($builder);
                $self->applyNotices($builder);

                if ($ready) {
                    $builder
                        ->title($self->completeTitle)
                        ->message($self->completeMessage)
                        ->addButton($self->completeButtonLabel, $self->redirectAfterComplete);
                } else {
                    $retryUrl = WebkernelRouter::url('setup/{token}/run', ['token' => $token]);
                    $builder
                        ->title($self->incompleteTitle)
                        ->message($self->incompleteMessage)
                        ->addButton('Try Again', $retryUrl);
                }

                $builder->render();
            },
        );
    }

    // ── Token lifecycle ───────────────────────────────────────────
    /**
     * @return array{HmacSigner, string}
     */
    private function resolveToken(): array
    {
        $projectSalt = hash('sha256', $this->basePath);
        $ttl         = $this->tokenTtl;

        if (is_file($this->tokenFile)) {
            $raw = @file_get_contents($this->tokenFile);
            if ($raw !== false) {
                $dot = strrpos($raw, '.');
                if ($dot !== false) {
                    $jsonPart = substr($raw, 0, $dot);
                    $decoded  = json_decode($jsonPart, true);
                    if (
                        is_array($decoded)
                        && isset($decoded['entropy'], $decoded['token'], $decoded['created_at'])
                        && is_string($decoded['entropy'])
                        && is_string($decoded['token'])
                        && is_int($decoded['created_at'])
                    ) {
                        $candidate = new HmacSigner($projectSalt . ':' . $decoded['entropy']);
                        $verified  = $candidate->verifyArray($raw);
                        if ($verified !== null && (time() - $decoded['created_at']) < $ttl) {
                            return [$candidate, $decoded['token']];
                        }
                    }
                }
            }
        }

        $entropy  = bin2hex(
            /** @disregard */
            function_exists('random_bytes') ? random_bytes(32): openssl_random_pseudo_bytes(32)
        );
        $signer   = new HmacSigner($projectSalt . ':' . $entropy);
        $newToken = $signer->token('setup', $this->basePath, (string) time());
        $signed   = $signer->signArray([
            'entropy'    => $entropy,
            'token'      => $newToken,
            'created_at' => time(),
        ]);

        @file_put_contents($this->tokenFile, $signed, LOCK_EX);

        return [$signer, $newToken];
    }

    // ── Static helpers ────────────────────────────────────────────
    public static function renderBadToken(): never
    {
        EmergencyPageBuilder::create()
            ->title('Setup Link Expired or Invalid')
            ->message(
                "This setup link is no longer valid.\n\n"
                . "This can happen if:\n"
                . "  - The link has expired (tokens are valid for 24 hours)\n"
                . "  - The URL was modified or shared from another server\n"
                . "  - Setup has already been completed\n\n"
                . "Reload the application root to get a fresh setup link."
            )
            ->severity('WARNING')
            ->code(403)
            ->systemState('SETUP TOKEN INVALID')
            ->footer('WEBKERNEL — SETUP SECURITY CHECK FAILED')
            ->addButton('Return to Application Root', '/')
            ->render();
    }
}
