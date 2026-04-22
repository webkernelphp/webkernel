<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webkernel Core Update</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #fff; color: #111827; line-height: 1.6; }
        @media (prefers-color-scheme: dark) { body { background: #0a0a0a; color: #f9fafb; } }
        
        .hero { max-width: 900px; margin: 0 auto; padding: 6rem 1.5rem 4rem; text-align: center; }
        .hero-badge { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; margin-bottom: 1.5rem; color: #666; }
        .hero-badge span { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        
        .hero h1 { font-size: clamp(2.5rem, 8vw, 3.5rem); font-weight: 700; letter-spacing: -0.02em; line-height: 1.1; margin-bottom: 1.5rem; }
        .hero p { font-size: 1.125rem; color: #666; max-width: 600px; margin: 0 auto 2rem; line-height: 1.8; }
        @media (prefers-color-scheme: dark) { .hero p { color: #aaa; } }
        
        .cta-group { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.375rem; font-weight: 500; border: none; cursor: pointer; font-size: 1rem; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #111827; color: white; }
        .btn-primary:hover:not(:disabled) { background: #1f2937; }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-secondary { background: transparent; color: #111827; border: 1px solid #e5e7eb; }
        .btn-secondary:hover { background: #f9fafb; border-color: #d1d5db; }
        @media (prefers-color-scheme: dark) { .btn-secondary { color: #f9fafb; border-color: #333; } .btn-secondary:hover { background: #1a1a1a; border-color: #444; } }
        
        .status { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #666; margin-top: 1rem; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; background: #f3f4f6; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
        @media (prefers-color-scheme: dark) { .status { color: #aaa; } .status-badge { background: #333; } }
        
        .features { background: #f9fafb; padding: 4rem 1.5rem; border-bottom: 1px solid #e5e7eb; }
        .features-inner { max-width: 1080px; margin: 0 auto; }
        .features h2 { font-size: 2rem; margin-bottom: 3rem; text-align: center; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .feature { background: white; padding: 2rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; }
        .feature h3 { font-size: 1.125rem; margin-bottom: 0.75rem; font-weight: 600; }
        .feature p { color: #666; font-size: 0.9375rem; line-height: 1.75; }
        @media (prefers-color-scheme: dark) { .features { background: #111827; border-bottom-color: #222; } .feature { background: #1a1a1a; border-color: #333; color: #f9fafb; } .feature p { color: #aaa; } }
        
        .pipeline { background: #111827; color: white; padding: 4rem 1.5rem; border-bottom: 1px solid #222; }
        .pipeline-inner { max-width: 1080px; margin: 0 auto; }
        .pipeline h2 { font-size: 2rem; margin-bottom: 3rem; text-align: center; }
        .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
        .step { border-left: 2px solid #10b981; padding-left: 1.5rem; }
        .step h3 { font-size: 1rem; margin-bottom: 0.5rem; font-weight: 600; }
        .step p { font-size: 0.875rem; color: #ccc; line-height: 1.6; }
        
        .stats { padding: 3rem 1.5rem; background: white; border-bottom: 1px solid #e5e7eb; }
        .stats-inner { max-width: 1080px; margin: 0 auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center; }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: #111827; font-family: monospace; }
        .stat-label { font-size: 0.875rem; color: #666; margin-top: 0.5rem; font-weight: 500; }
        @media (prefers-color-scheme: dark) { .stats { background: #0a0a0a; border-bottom-color: #222; } .stat-value { color: #f9fafb; } .stat-label { color: #aaa; } }
        
        .footer { background: #f9fafb; padding: 3rem 1.5rem; border-top: 1px solid #e5e7eb; }
        .footer-inner { max-width: 1080px; margin: 0 auto; }
        .footer-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .footer-link { padding: 1rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; text-decoration: none; color: #111827; transition: all 0.2s; display: block; }
        .footer-link:hover { border-color: #d1d5db; background: white; }
        .footer-link-label { font-weight: 600; font-size: 0.9375rem; }
        .footer-link-desc { font-size: 0.8125rem; color: #999; margin-top: 0.25rem; }
        @media (prefers-color-scheme: dark) { .footer { background: #111827; border-top-color: #222; } .footer-link { background: #1a1a1a; border-color: #333; color: #f9fafb; } .footer-link:hover { background: #222; border-color: #444; } .footer-link-desc { color: #777; } }
        
        .progress-section { padding: 2rem 1.5rem; background: white; border-bottom: 1px solid #e5e7eb; }
        .progress-inner { max-width: 1080px; margin: 0 auto; }
        .progress-bar { height: 3px; background: #e5e7eb; border-radius: 99px; overflow: hidden; margin-top: 1rem; }
        .progress-fill { height: 100%; background: #111827; border-radius: 99px; transition: width 0.5s ease; }
        .error-block { background: #fee2e2; border-left: 3px solid #dc2626; padding: 1rem; border-radius: 0 0.375rem 0.375rem 0; color: #991b1b; font-size: 0.875rem; margin-top: 1rem; }
        @media (prefers-color-scheme: dark) { .progress-section { background: #0a0a0a; border-bottom-color: #222; } }
        
        @media (max-width: 768px) {
            .hero { padding: 3rem 1rem 2rem; }
            .hero h1 { font-size: 1.875rem; }
            .hero p { font-size: 1rem; }
            .cta-group { flex-direction: column; align-items: center; }
            .btn { width: 100%; max-width: 300px; }
            .features, .pipeline, .stats, .footer { padding: 2rem 1rem; }
            .features h2, .pipeline h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-badge">
            <span></span>
            Webkernel Core — Update Center
        </div>

        <h1>
            @if($isUpToDate)
                Your kernel is<br>up to date
            @else
                A new update<br>is available
            @endif
        </h1>

        <p>
            @if($isUpToDate)
                You're running the latest version of Webkernel Core. All systems optimal.
            @else
                Update v{{ $latestVersion }} includes performance improvements, security patches, and new features.
            @endif
        </p>

        <div class="cta-group">
            @if(!$isUpToDate)
                <button class="btn btn-primary" wire:click="updateKernel" @if($isUpdating) disabled @endif>
                    @if($isUpdating) Updating... @else Upgrade to v{{ $latestVersion }} @endif
                </button>
            @else
                <button class="btn btn-primary" disabled>Kernel Up to Date</button>
            @endif

            <button class="btn btn-secondary" wire:click="checkForUpdates">Check for Updates</button>
        </div>

        <div class="status">
            @if($isUpToDate)
                <span style="color: #10b981;">✓</span>
                <span class="status-badge" style="background: #ecfdf5; color: #065f46;">v{{ $currentVersion }}</span>
            @else
                <span style="color: #f59e0b;">⚠</span>
                <span class="status-badge" style="background: #fef3c7; color: #92400e;">Update available</span>
            @endif
            <span>Checked {{ $lastChecked }}</span>
        </div>
    </div>

    @if(!empty($updateStatus) || !empty($updateError))
    <div class="progress-section">
        <div class="progress-inner">
            @if(!empty($updateStatus))
                <div style="font-size: 0.875rem; margin-bottom: 0.5rem;">{{ $updateStatus }}</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $isUpdating ? '60%' : '100%' }};"></div>
                </div>
            @endif
            @if(!empty($updateError))
                <div class="error-block">{{ $updateError }}</div>
            @endif
        </div>
    </div>
    @endif

    <div class="features">
        <div class="features-inner">
            <h2>What's in this update</h2>
            <div class="features-grid">
                <div class="feature">
                    <h3>Cryptographic Backup</h3>
                    <p>Automatic SHA-256 fingerprint snapshot before any changes. Full rollback capability.</p>
                </div>
                <div class="feature">
                    <h3>Atomic Installation</h3>
                    <p>Zero-downtime kernel swap. Running processes pause, kernel updates, processes resume.</p>
                </div>
                <div class="feature">
                    <h3>Configuration Preserved</h3>
                    <p>Your custom settings and module overrides stay intact.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="stats">
        <div class="stats-inner">
            <div class="stats-grid">
                <div><div class="stat-value">{{ $phpVersion }}</div><div class="stat-label">PHP Runtime</div></div>
                <div><div class="stat-value">{{ $laravelVersion }}</div><div class="stat-label">Laravel</div></div>
                <div><div class="stat-value">{{ $filamentVersion }}</div><div class="stat-label">Filament</div></div>
            </div>
        </div>
    </div>

    <div class="pipeline">
        <div class="pipeline-inner">
            <h2>Upgrade Pipeline</h2>
            <div class="steps">
                <div class="step"><h3>1. Backup</h3><p>Cryptographic snapshot of your current kernel is stored before any changes.</p></div>
                <div class="step"><h3>2. Download & Verify</h3><p>New kernel fetched from registry. GPG signature verified against public key.</p></div>
                <div class="step"><h3>3. Preserve Config</h3><p>Custom settings and module overrides kept untouched. Only core files replaced.</p></div>
                <div class="step"><h3>4. Hot-Swap</h3><p>Running processes paused, kernel swapped atomically, workers automatically resumed.</p></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-inner">
            <div class="footer-links">
                <a href="https://webkernelphp.com/docs" target="_blank" class="footer-link">
                    <div class="footer-link-label">Documentation</div>
                    <div class="footer-link-desc">Full update guide and API reference</div>
                </a>
                <a href="https://github.com/webkernelphp/webkernel" target="_blank" class="footer-link">
                    <div class="footer-link-label">GitHub Repository</div>
                    <div class="footer-link-desc">View source code and releases</div>
                </a>
                <a href="https://webkernelphp.com/marketplace" target="_blank" class="footer-link">
                    <div class="footer-link-label">Marketplace</div>
                    <div class="footer-link-desc">Browse available modules</div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
