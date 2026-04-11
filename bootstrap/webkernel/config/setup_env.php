<?php
declare(strict_types=1);
/**
 * ═══════════════════════════════════════════════════════════════════
 *  WebKernel — Pre-boot Environment Guard
 *  bootstrap/webkernel/config/setup_env.php
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Self-managing first-run setup. No query strings. No ugly params.
 *  Routes live under /__webkernel-app/setup/{token}.
 *
 *  Flow
 *  ────
 *  1. Both .env + database.sqlite exist → return immediately.
 *     Laravel boots normally. RootController takes over.
 *
 *  2. Either file is missing:
 *     a. A server-signed setup token is generated (or reused from
 *        the signed token file). The token is HMAC-derived — opaque,
 *        unguessable, tied to this server's secret.
 *     b. Routes are registered on WebKernelRouter:
 *          /__webkernel-app/setup/{token}           → preview page
 *          /__webkernel-app/setup/{token}/run        → execute setup
 *          /__webkernel-app/setup/{token}/complete   → post-setup page
 *     c. WebKernelRouter::dispatch() is called. If the URL matches,
 *        the handler runs and execution terminates.
 *     d. If the URL does not match any setup route (e.g. user hits /),
 *        a redirect is emitted to the preview page.
 *
 *  Token lifecycle
 *  ───────────────
 *  Tokens are stored in BASE_PATH/.deployment_setup_token (HMAC-signed JSON).
 *  They expire after 24 hours. A new token is generated if:
 *    - The token file does not exist
 *    - The signature is invalid (tamper detected)
 *    - The token has expired
 *    - Setup has already completed (file is deleted post-setup)
 *
 *  Security
 *  ────────
 *  The HMAC secret is derived from:
 *    - A project-level salt (BASE_PATH hash)
 *    - A per-boot entropy component stored in the token file
 *  Without the secret, an attacker cannot forge a valid token.
 *  The setup routes are never reachable once both files exist.
 *
 *  Prerequisite guards
 *  ───────────────────
 *  Hard server conditions are checked before any route is registered.
 *  These cannot be fixed by the setup wizard — PHP version, extensions,
 *  directory writability. Failure renders a CRITICAL page immediately.
 *
 *  Requirements
 *  ────────────
 *  BASE_PATH must be defined before this file is included.
 *  renderCriticalErrorHtml.php must be loaded (provides all classes).
 * ═══════════════════════════════════════════════════════════════════
 */

(static function (): void {

    // ── §A Fast-path ──────────────────────────────────────────────
    // Both pre-boot files exist → nothing to do, let Laravel boot.
    $envPath = BASE_PATH . '/.env';
    $dbPath  = BASE_PATH . '/database/database.sqlite';

    if (is_file($envPath) && is_file($dbPath)) {
        return;
    }

    // ── §B Prerequisite guards ────────────────────────────────────
    // Conditions the setup wizard cannot fix. Show a CRITICAL block.
    $prerequisites = [
        'PHP 8.1 or newer is required'
            => static fn(): bool => PHP_VERSION_ID >= 80100,
        'The pdo_sqlite extension must be enabled'
            => static fn(): bool => extension_loaded('pdo_sqlite'),
        'The application root must be writable by the web server'
            => static fn(): bool => is_dir(BASE_PATH) && is_writable(BASE_PATH),
        'An entropy source (openssl or random_bytes) must be available'
            => static fn(): bool =>
                function_exists('openssl_random_pseudo_bytes')
                || function_exists('random_bytes'),
    ];

    foreach ($prerequisites as $description => $check) {
        if (!$check()) {
            EmergencyPageBuilder::create()
                ->title('Setup Cannot Proceed')
                ->message(
                    "A required server condition is not satisfied:\n\n"
                    . "  ✕  {$description}\n\n"
                    . "Fix the server configuration and reload this page."
                )
                ->severity('CRITICAL')
                ->code(500)
                ->systemState('ENVIRONMENT ERROR')
                ->footer('SERVER CONFIGURATION ERROR — SETUP BLOCKED')
                ->addButton('Reload', '/')
                ->render();
        }
    }

    // ── §C Token file + signer + token ───────────────────────────
    // Single call that does everything atomically:
    //   1. Read existing token file (if any)
    //   2. Verify its HMAC signature
    //   3. Check expiry (24 h)
    //   4. Return [signer, token] — reused or freshly generated
    $tokenFile = BASE_PATH . '/.deployment_setup_token';
    [$signer, $token] = self_setup_resolve($tokenFile);

    // ── §D Resolve paths used in closures ─────────────────────────
    $examplePath = BASE_PATH . '/.env.example';
    $dbDir       = dirname($dbPath);

    // ── §E Register routes ────────────────────────────────────────
    //
    //   GET /__webkernel-app/setup/{token}
    //       Preview page — lists what will happen, no files touched.
    //       Single "Proceed" link → /run sub-route.
    //
    //   GET /__webkernel-app/setup/{token}/run
    //       Execute setup — closures run, files written.
    //       On success, redirects to /complete sub-route.
    //
    //   GET /__webkernel-app/setup/{token}/complete
    //       Success page — confirms everything is ready.
    //       "Open Application" link → /.

    // ── Route: preview ────────────────────────────────────────────
    WebKernelRouter::register(
        'setup/{token}',
        static function (array $params) use ($token, $signer): never {
            if (!self_setup_verify_token($params['token'], $token, $signer)) {
                self_setup_bad_token();
            }

            $runUrl      = WebKernelRouter::url('setup/{token}/run',      ['token' => $token]);
            $canonicalUrl = WebKernelRouter::url('setup/{token}',          ['token' => $token]);

            EmergencyPageBuilder::create()
                ->title('First-run Setup Required')
                ->severity('SETUP')
                ->code(200)
                ->systemState('FIRST-RUN SETUP')
                ->canonicalize($canonicalUrl)
                ->footer('WEBKERNEL — REVIEW AND CONFIRM BEFORE PROCEEDING')
                ->message(
                    '<b>This application has not been initialised yet.</b>'
                    . ' The following actions will be performed on this server.'
                    . ' Review them carefully, then click Proceed when ready.'
                )
                ->step('Read environment template (.env.example)',      pending: true)
                ->step('Generate secure application key (APP_KEY)',     pending: true)
                ->step('Write environment file (.env)',                  pending: true)
                ->step('Create SQLite database file',                   pending: true)
                ->step('Run database migrations (deferred to boot)',    pending: true)
                ->submitStep('Proceed with Setup', $runUrl)
                ->render();
        },
    );

    // ── Route: run ────────────────────────────────────────────────
    WebKernelRouter::register(
        'setup/{token}/run',
        static function (array $params) use ($token, $signer, $envPath, $dbPath, $dbDir, $examplePath, $tokenFile): never {
            if (!self_setup_verify_token($params['token'], $token, $signer)) {
                self_setup_bad_token();
            }

            $completeUrl  = WebKernelRouter::url('setup/{token}/complete', ['token' => $token]);
            $canonicalUrl = WebKernelRouter::url('setup/{token}/run',      ['token' => $token]);

            // Shared mutable state for closures
            $state = ['envContent' => '', 'appKey' => null];

            EmergencyPageBuilder::create()
                ->title('Setting Up Your Environment')
                ->severity('SETUP')
                ->code(200)
                ->systemState('SETUP IN PROGRESS')
                ->canonicalize($canonicalUrl)
                ->footer('WEBKERNEL — FIRST-RUN SETUP')

                // ── Step 0: read template ──────────────────────────
                ->step(
                    label: 'Reading environment template',
                    closure: static function () use ($envPath, $examplePath, &$state): bool|string {
                        if (is_file($envPath)) {
                            return true;
                        }
                        if (!is_file($examplePath)) {
                            $state['envContent'] = '';
                            return true;
                        }
                        $content = @file_get_contents($examplePath);
                        if ($content === false) {
                            return "Cannot read {$examplePath} — check file permissions.";
                        }
                        $state['envContent'] = $content;
                        return true;
                    },
                )

                // ── Step 1: generate APP_KEY ───────────────────────
                ->step(
                    label: 'Generating secure application key',
                    closure: static function () use ($envPath, &$state): bool|string {
                        if (is_file($envPath)) {
                            return true;
                        }
                        try {
                            $raw = function_exists('openssl_random_pseudo_bytes')
                                ? (string) openssl_random_pseudo_bytes(32)
                                : random_bytes(32);
                        } catch (\Throwable $e) {
                            return 'Entropy source failed: ' . $e->getMessage();
                        }
                        $state['appKey'] = 'base64:' . base64_encode($raw);
                        return true;
                    },
                )

                // ── Step 2: write .env ─────────────────────────────
                ->step(
                    label: 'Writing environment file (.env)',
                    closure: static function () use ($envPath, &$state): bool|string {
                        if (is_file($envPath)) {
                            return true;
                        }
                        if ($state['appKey'] === null) {
                            return 'APP_KEY was not generated — cannot write .env.';
                        }
                        $content = $state['envContent'];
                        $key     = $state['appKey'];
                        $content = preg_match('/^APP_KEY=/m', $content)
                            ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $content)
                            : "APP_KEY={$key}\n" . $content;
                        if (@file_put_contents($envPath, $content) === false) {
                            return 'Write failed — check permissions on ' . BASE_PATH;
                        }
                        return true;
                    },
                )

                // ── Step 3: create database file ───────────────────
                ->step(
                    label: 'Creating SQLite database file',
                    closure: static function () use ($dbPath, $dbDir): bool|string {
                        if (is_file($dbPath)) {
                            return true;
                        }
                        if (!is_dir($dbDir)) {
                            return 'The database/ directory does not exist — check project structure.';
                        }
                        if (!@touch($dbPath)) {
                            return "Cannot create database file — check permissions on {$dbDir}";
                        }
                        return true;
                    },
                )

                // ── Step 4: migrations (deferred) ──────────────────
                ->step(label: 'Running database migrations', pending: true)

                // ── Submit → complete page (no query strings) ──────
                ->submitStep('Review Setup Result', $completeUrl)
                ->render();
        },
    );

    // ── Route: complete ───────────────────────────────────────────
    WebKernelRouter::register(
        'setup/{token}/complete',
        static function (array $params) use ($token, $signer, $envPath, $dbPath, $tokenFile): never {
            if (!self_setup_verify_token($params['token'], $token, $signer)) {
                self_setup_bad_token();
            }

            $ready        = is_file($envPath) && is_file($dbPath);
            $canonicalUrl = WebKernelRouter::url('setup/{token}/complete', ['token' => $token]);

            if ($ready) {
                // Burn the token — setup routes no longer valid
                @unlink($tokenFile);
            }

            $builder = EmergencyPageBuilder::create()
                ->severity($ready ? 'SETUP' : 'WARNING')
                ->code($ready ? 200 : 500)
                ->systemState($ready ? 'SETUP COMPLETE' : 'SETUP INCOMPLETE')
                ->canonicalize($canonicalUrl)
                ->footer('WEBKERNEL — FIRST-RUN SETUP');

            if ($ready) {
                $builder
                    ->title('Setup Complete')
                    ->message(
                        '<b>The environment has been initialised successfully.</b>'
                        . ' Database migrations will run automatically on first boot.'
                        . ' Click the button below to open the application.'
                    )
                    ->submitStep('Open Application', '/');
            } else {
                $builder
                    ->title('Setup Incomplete')
                    ->message(
                        'One or more setup steps did not complete successfully.'
                        . ' Check server permissions and try again.'
                    )
                    ->addButton('Try Again', WebKernelRouter::url('setup/{token}/run', ['token' => $token]));
            }

            $builder->render();
        },
    );

    // ── §F Dispatch ───────────────────────────────────────────────
    // Try to match the current URL against registered setup routes.
    if (WebKernelRouter::dispatch()) {
        // Handler ran and terminated (or returned) — stop here.
        exit(0);
    }

    // ── §G Fallback redirect ──────────────────────────────────────
    // URL did not match any setup route (e.g. user hit /).
    // Redirect to the preview page with the canonical token URL.
    // The browser gets a clean, signed URL — no query strings.
    $previewUrl = WebKernelRouter::url('setup/{token}', ['token' => $token]);
    header('Location: ' . $previewUrl, true, 302);
    exit(0);

})();

// ── Setup-local helpers (file-scoped, not class members) ──────────
// Prefixed `self_setup_` to avoid collisions with application code.

/**
 * Single-pass token resolution.
 *
 * Atomic sequence — entropy is generated ONCE and the same value
 * is used to build the signer AND stored in the token file.
 * No second call to any entropy source after the signer exists.
 *
 * Token file layout (HMAC-signed JSON):
 *   { "entropy": "<hex>", "token": "<url-safe-b64>", "created_at": <unix> }
 *
 * The HMAC secret = sha256(BASE_PATH) + ":" + entropy
 * That ties the token to this specific installation path.
 *
 * Returns [HmacSigner $signer, string $token]
 *
 * @return array{HmacSigner, string}
 */
function self_setup_resolve(string $tokenFile): array
{
    $projectSalt = hash('sha256', BASE_PATH);
    $ttl         = 86400; // 24 hours

    // ── Try to load and reuse an existing valid token file ────────
    if (is_file($tokenFile)) {
        $raw = @file_get_contents($tokenFile);

        if ($raw !== false) {
            // The file is "entropy-json . "." . hmac"
            // We need the entropy to rebuild the signer before we can
            // verify the HMAC — so extract entropy from the JSON prefix
            // WITHOUT trusting it yet, build the signer, then verify.
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
                    // Rebuild signer from the stored entropy
                    $candidate = new HmacSigner($projectSalt . ':' . $decoded['entropy']);

                    // NOW verify the full file's HMAC signature
                    $verified = $candidate->verifyArray($raw);

                    if (
                        $verified !== null
                        && (time() - $decoded['created_at']) < $ttl
                    ) {
                        // Valid, unexpired, untampered — reuse it
                        return [$candidate, $decoded['token']];
                    }
                }
            }
        }
    }

    // ── Generate a fresh token ────────────────────────────────────
    // One entropy value, used for both the signer secret and the file.
    $entropy = bin2hex(
        function_exists('random_bytes')
            ? random_bytes(32)
            : openssl_random_pseudo_bytes(32)
    );

    $signer   = new HmacSigner($projectSalt . ':' . $entropy);
    $newToken = $signer->token('setup', BASE_PATH, (string) time());

    $signed = $signer->signArray([
        'entropy'    => $entropy,
        'token'      => $newToken,
        'created_at' => time(),
    ]);

    @file_put_contents($tokenFile, $signed, LOCK_EX);

    return [$signer, $newToken];
}

/**
 * Constant-time token comparison.
 * The signer parameter is available for future signed-URL extensions.
 */
function self_setup_verify_token(string $urlToken, string $storedToken, HmacSigner $signer): bool
{
    return hash_equals($storedToken, $urlToken);
}

/**
 * Render a security block and terminate on token mismatch/expiry.
 */
function self_setup_bad_token(): never
{
    EmergencyPageBuilder::create()
        ->title('Setup Link Expired or Invalid')
        ->message(
            "This setup link is no longer valid.\n\n"
            . "This can happen if:\n"
            . "  · The link has expired (tokens are valid for 24 hours)\n"
            . "  · The URL was modified or shared from another server\n"
            . "  · Setup has already been completed\n\n"
            . "Reload the application root to get a fresh setup link."
        )
        ->severity('WARNING')
        ->code(403)
        ->systemState('SETUP TOKEN INVALID')
        ->footer('WEBKERNEL — SETUP SECURITY CHECK FAILED')
        ->addButton('Return to Application Root', '/')
        ->render();
}
