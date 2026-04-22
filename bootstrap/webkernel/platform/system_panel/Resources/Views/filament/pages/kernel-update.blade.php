<x-filament-panels::page>
@php
    $steps = [
        [
            'icon'        => 'heroicon-o-archive-box-arrow-down',
            'label'       => 'Automatic Backup',
            'description' => 'A cryptographic snapshot of your active kernel is stored before any change is applied. SHA-256 fingerprints are recorded in the manifest.',
            'badge'       => 'AUTO',
        ],
        [
            'icon'        => 'heroicon-o-arrow-down-tray',
            'label'       => 'Download & Verify',
            'description' => 'The new kernel is fetched from the official Webkernel registry. Its GPG signature is verified against the published public key before extraction.',
            'badge'       => 'SIGNED',
        ],
        [
            'icon'        => 'heroicon-o-shield-check',
            'label'       => 'Preserve Configurations',
            'description' => 'Custom directories, environment variables, and module overrides are kept untouched. Only the core bootstrap files are replaced.',
            'badge'       => 'SAFE',
        ],
        [
            'icon'        => 'heroicon-o-arrow-path',
            'label'       => 'Hot-Swap & Resume',
            'description' => 'Running processes are briefly paused, the kernel files are swapped atomically, and all workers are automatically resumed.',
            'badge'       => 'ATOMIC',
        ],
    ];

    $features = [
        [
            'icon'  => 'heroicon-o-cube-transparent',
            'title' => 'Modular Architecture',
            'body'  => 'Install, enable or disable packages via Composer. Each module is a self-contained unit with its own routes, views and migrations.',
        ],
        [
            'icon'  => 'heroicon-o-lock-closed',
            'title' => 'Digital Sovereignty',
            'body'  => 'No forced PaaS. No vendor lock-in. Your data stays on your own infrastructure, in the jurisdiction you choose.',
        ],
        [
            'icon'  => 'heroicon-o-bolt',
            'title' => 'Octane-Ready Boot',
            'body'  => 'Constants, config and module manifests are resolved once, cached and never re-parsed on warm requests.',
        ],
        [
            'icon'  => 'heroicon-o-shield-exclamation',
            'title' => 'Integrity at Boot',
            'body'  => 'CoreManifest + SealEnforcer verify cryptographic fingerprints on every startup, before any user request hits your application.',
        ],
        [
            'icon'  => 'heroicon-o-users',
            'title' => 'Multi-Role Panels',
            'body'  => 'Filament 5 panels per role — Admin, Client, Partner — each with its own data scope and permission model.',
        ],
        [
            'icon'  => 'heroicon-o-arrow-path',
            'title' => 'Resilient Installer',
            'body'  => 'Progress is saved to disk. Close the browser mid-install and it resumes exactly where it stopped.',
        ],
    ];

    $stack = [
        ['label' => 'Language',     'value' => 'PHP 8.4+'],
        ['label' => 'Framework',    'value' => 'Laravel 13+'],
        ['label' => 'UI / Panels',  'value' => 'Filament 5+'],
        ['label' => 'Reactivity',   'value' => 'Livewire 4'],
        ['label' => 'Performance',  'value' => 'Laravel Octane'],
        ['label' => 'Modules',      'value' => 'Arcanes Loader'],
        ['label' => 'Integrity',    'value' => 'CoreManifest + SealEnforcer'],
        ['label' => 'License',      'value' => 'EPL 2.0 / Commercial'],
    ];

    $releases = [
        [
            'version'  => '1.0.1',
            'codename' => 'Sovereign',
            'date'     => '2025-03-27',
            'notes'    => 'Performance optimizations, SealEnforcer v2, Arcanes loader refactor.',
            'current'  => true,
        ],
        [
            'version'  => '1.0.0',
            'codename' => 'Sovereign',
            'date'     => '2025-01-15',
            'notes'    => 'First stable release. CoreManifest, multi-panel support, Octane boot.',
            'current'  => false,
        ],
        [
            'version'  => '0.9.8',
            'codename' => 'Waterfall',
            'date'     => '2024-11-02',
            'notes'    => 'Beta. Module system finalized. Installer resilience improvements.',
            'current'  => false,
        ],
    ];

    $docLinks = [
        ['icon' => 'heroicon-o-book-open',          'label' => 'Documentation',    'url' => 'https://webkernelphp.com/docs'],
        ['icon' => 'heroicon-o-globe-alt',           'label' => 'webkernelphp.com', 'url' => 'https://webkernelphp.com/'],
        ['icon' => 'heroicon-o-squares-plus',        'label' => 'Marketplace',      'url' => 'https://webkernelphp.com/marketplace'],
        ['icon' => 'heroicon-o-code-bracket-square', 'label' => 'GitHub',           'url' => 'https://github.com/webkernelphp/webkernel'],
        ['icon' => 'heroicon-o-archive-box',         'label' => 'Packagist',        'url' => 'https://packagist.org/packages/webkernel/webkernel'],
        ['icon' => 'heroicon-o-building-office-2',   'label' => 'Numerimondes',     'url' => 'https://webkernelphp.com/about'],
    ];

    $videoId = 'ANy5LtTPLb0';

    $usedBy = [
        ['emoji' => '🏛️', 'label' => 'Government'],
        ['emoji' => '🏦', 'label' => 'Finance'],
        ['emoji' => '🛒', 'label' => 'E-Commerce'],
        ['emoji' => '⚡', 'label' => 'Energy'],
        ['emoji' => '🏥', 'label' => 'Healthcare'],
        ['emoji' => '🚀', 'label' => 'Startups'],
        ['emoji' => '🎓', 'label' => 'Education'],
        ['emoji' => '🏢', 'label' => 'Enterprise'],
    ];
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     SCOPED STYLES
══════════════════════════════════════════════════════════════════════ --}}
<style>
/* ── Design tokens ──────────────────────────────────────────────────── */
.webkernel-upgrade-page-root {
    --wk-accent:          var(--primary-600, #6d28d9);
    --wk-accent-dim:      color-mix(in oklch, var(--wk-accent) 10%, transparent);
    --wk-accent-border:   color-mix(in oklch, var(--wk-accent) 25%, transparent);
    --wk-accent-glow:     color-mix(in oklch, var(--wk-accent) 35%, transparent);
    --wk-surface:         var(--gray-50, #f9fafb);
    --wk-surface-raised:  var(--gray-100, #f3f4f6);
    --wk-surface-card:    #ffffff;
    --wk-border:          var(--gray-200, #e5e7eb);
    --wk-text:            var(--gray-900, #111827);
    --wk-text-muted:      var(--gray-500, #6b7280);
    --wk-text-faint:      var(--gray-400, #9ca3af);
    --wk-mono:            'JetBrains Mono', 'Fira Mono', ui-monospace, monospace;
    --wk-radius:          6px;
    --wk-radius-lg:       12px;
    --wk-radius-xl:       18px;
    --wk-success:         var(--success-600, #16a34a);
    --wk-warning:         var(--warning-600, #d97706);
    --wk-danger:          var(--danger-600, #dc2626);
    --wk-danger-dim:      color-mix(in oklch, var(--wk-danger) 10%, transparent);
    --wk-danger-border:   color-mix(in oklch, var(--wk-danger) 25%, transparent);
    --wk-warning-dim:     color-mix(in oklch, var(--wk-warning) 10%, transparent);
    --wk-warning-border:  color-mix(in oklch, var(--wk-warning) 25%, transparent);
    font-family: 'Geist', 'DM Sans', ui-sans-serif, system-ui, sans-serif;
    display: flex;
    flex-direction: column;
    gap: 0;
    background: var(--wk-surface);
}
.dark .webkernel-upgrade-page-root {
    --wk-surface:        #0a0a0a;
    --wk-surface-raised: #111111;
    --wk-surface-card:   #161616;
    --wk-border:         #222222;
    --wk-text:           #ededed;
    --wk-text-muted:     #888888;
    --wk-text-faint:     #555555;
}

/* ── Google Fonts (Geist-like feel) ─────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=DM+Mono:wght@400;500&display=swap');

/* ══════════════════════════════════════════════════════════════
   SECTION SPACING
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-section-gap {
    height: 1px;
    background: var(--wk-border);
    margin: 0;
}
.webkernel-upgrade-page-inner {
    max-width: 1080px;
    margin: 0 auto;
    padding: 0 2rem;
    width: 100%;
}

/* ══════════════════════════════════════════════════════════════
   TOP ANNOUNCEMENT BAR
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-announce {
    background: var(--wk-text);
    color: var(--wk-surface);
    padding: 0.6rem 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    letter-spacing: -0.01em;
    border-bottom: 1px solid transparent;
    cursor: default;
}
.dark .webkernel-upgrade-page-announce {
    background: var(--wk-surface-raised);
    color: var(--wk-text);
    border-bottom-color: var(--wk-border);
}
.webkernel-upgrade-page-announce-badge {
    background: var(--wk-accent);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 3px;
    flex-shrink: 0;
}
.webkernel-upgrade-page-announce-arrow {
    opacity: 0.5;
    font-size: 0.75rem;
}

/* ══════════════════════════════════════════════════════════════
   HERO SECTION
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-hero {
    position: relative;
    padding: 6rem 2rem 5rem;
    text-align: center;
    overflow: hidden;
    background: var(--wk-surface-card);
    border-bottom: 1px solid var(--wk-border);
}
.dark .webkernel-upgrade-page-hero {
    background: var(--wk-surface);
}
.webkernel-upgrade-page-hero-grid {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(color-mix(in oklch, var(--wk-border) 80%, transparent) 1px, transparent 1px),
        linear-gradient(90deg, color-mix(in oklch, var(--wk-border) 80%, transparent) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black 20%, transparent 100%);
    opacity: 0.5;
}
.webkernel-upgrade-page-hero-glow {
    position: absolute;
    top: -120px;
    left: 50%;
    transform: translateX(-50%);
    width: 700px;
    height: 500px;
    background: radial-gradient(ellipse at center, var(--wk-accent-glow) 0%, transparent 65%);
    pointer-events: none;
    filter: blur(2px);
}
.webkernel-upgrade-page-hero-inner {
    position: relative;
    z-index: 1;
    max-width: 780px;
    margin: 0 auto;
}
.webkernel-upgrade-page-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--wk-border);
    background: var(--wk-surface-raised);
    border-radius: 99px;
    padding: 4px 14px 4px 6px;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: var(--wk-text-muted);
    margin-bottom: 1.75rem;
    font-family: var(--wk-mono);
}
.webkernel-upgrade-page-eyebrow-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--wk-accent);
    display: block;
    flex-shrink: 0;
    animation: webkernel-upgrade-page-blink 2s ease-in-out infinite;
}
@keyframes webkernel-upgrade-page-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.3; }
}
.webkernel-upgrade-page-hero-title {
    font-size: clamp(2.5rem, 6vw, 4.25rem);
    font-weight: 800;
    color: var(--wk-text);
    line-height: 1.04;
    letter-spacing: -0.05em;
    margin: 0 0 1.25rem;
    font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
}
.webkernel-upgrade-page-hero-title-muted {
    color: var(--wk-text-faint);
}
.webkernel-upgrade-page-hero-title-accent {
    color: var(--wk-accent);
}
.webkernel-upgrade-page-hero-sub {
    font-size: 1.0625rem;
    color: var(--wk-text-muted);
    line-height: 1.8;
    max-width: 560px;
    margin: 0 auto 2.25rem;
    font-weight: 400;
}
.webkernel-upgrade-page-hero-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.875rem;
    flex-wrap: wrap;
    margin-bottom: 3rem;
}
.webkernel-upgrade-page-hero-badges {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.webkernel-upgrade-page-hero-checked {
    font-size: 0.7rem;
    color: var(--wk-text-faint);
    font-family: var(--wk-mono);
    letter-spacing: 0.05em;
}

/* ══════════════════════════════════════════════════════════════
   RUNTIME STATS TICKER
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-bottom: 1px solid var(--wk-border);
    background: var(--wk-surface);
}
.webkernel-upgrade-page-stat {
    padding: 1.625rem 1.75rem;
    border-right: 1px solid var(--wk-border);
    text-align: center;
}
.webkernel-upgrade-page-stat:last-child { border-right: none; }
.webkernel-upgrade-page-stat-val {
    font-size: 1.375rem;
    font-weight: 800;
    color: var(--wk-text);
    letter-spacing: -0.04em;
    line-height: 1;
    margin-bottom: 5px;
    font-family: var(--wk-mono);
}
.webkernel-upgrade-page-stat-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--wk-text-faint);
    font-family: var(--wk-mono);
}

/* ══════════════════════════════════════════════════════════════
   UPDATE STATUS STRIP
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-status-wrap {
    padding: 1rem 2rem;
    background: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
}
.webkernel-upgrade-page-status-inner {
    max-width: 1080px;
    margin: 0 auto;
}
.webkernel-upgrade-page-status-strip {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.125rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    background: var(--wk-surface-card);
}
.dark .webkernel-upgrade-page-status-strip {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    animation: webkernel-upgrade-page-pulse 1.6s cubic-bezier(.4,0,.6,1) infinite;
}
@keyframes webkernel-upgrade-page-pulse {
    0%, 100% { opacity: 1; } 50% { opacity: .25; }
}
.webkernel-upgrade-page-status-text {
    font-size: 0.875rem;
    color: var(--wk-text);
    flex: 1;
    font-family: var(--wk-mono);
}
.webkernel-upgrade-page-progress-track {
    height: 2px;
    background: var(--wk-border);
    border-radius: 99px;
    overflow: hidden;
    margin-top: 0.5rem;
}
.webkernel-upgrade-page-progress-fill {
    height: 100%;
    background: var(--wk-accent);
    border-radius: 99px;
    transition: width 0.5s ease;
}
.webkernel-upgrade-page-error-block {
    background: var(--wk-danger-dim);
    border: 1px solid var(--wk-danger-border);
    border-left: 3px solid var(--wk-danger);
    border-radius: 0 var(--wk-radius) var(--wk-radius) 0;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
    color: var(--wk-danger);
    font-family: var(--wk-mono);
    margin-top: 0.625rem;
}

/* ══════════════════════════════════════════════════════════════
   WHAT WEBKERNEL DELIVERS — Feature grid (Next.js style)
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-features-section {
    background: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
    padding: 5rem 2rem;
}
.webkernel-upgrade-page-section-eyebrow {
    text-align: center;
    font-family: var(--wk-mono);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--wk-text-faint);
    margin-bottom: 1rem;
}
.webkernel-upgrade-page-section-title {
    text-align: center;
    font-size: clamp(1.625rem, 3vw, 2.25rem);
    font-weight: 800;
    color: var(--wk-text);
    letter-spacing: -0.04em;
    line-height: 1.1;
    margin: 0 0 0.75rem;
    font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
}
.webkernel-upgrade-page-section-sub {
    text-align: center;
    font-size: 0.9375rem;
    color: var(--wk-text-muted);
    line-height: 1.75;
    max-width: 520px;
    margin: 0 auto 3rem;
}
.webkernel-upgrade-page-features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
    background: var(--wk-surface-card);
    max-width: 1080px;
    margin: 0 auto;
}
.dark .webkernel-upgrade-page-features-grid {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-feature {
    padding: 2rem 1.875rem;
    border-right: 1px solid var(--wk-border);
    border-bottom: 1px solid var(--wk-border);
    transition: background 0.15s ease;
    position: relative;
}
.webkernel-upgrade-page-feature::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--wk-accent-dim), transparent);
    opacity: 0;
    transition: opacity 0.2s;
}
.webkernel-upgrade-page-feature:hover { background: var(--wk-surface-raised); }
.webkernel-upgrade-page-feature:hover::before { opacity: 1; }
.webkernel-upgrade-page-feature:nth-child(3n) { border-right: none; }
.webkernel-upgrade-page-feature:nth-last-child(-n+3) { border-bottom: none; }
.webkernel-upgrade-page-feature-icon {
    width: 36px;
    height: 36px;
    background: var(--wk-accent-dim);
    border: 1px solid var(--wk-accent-border);
    border-radius: var(--wk-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.125rem;
    color: var(--wk-accent);
}
.webkernel-upgrade-page-feature-icon svg { width: 16px; height: 16px; }
.webkernel-upgrade-page-feature-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--wk-text);
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
}
.webkernel-upgrade-page-feature-body {
    font-size: 0.8125rem;
    color: var(--wk-text-muted);
    line-height: 1.75;
    margin: 0;
}

/* ══════════════════════════════════════════════════════════════
   UPGRADE PIPELINE — dark showcase section
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-pipeline-section {
    background: var(--wk-text);
    color: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
    padding: 5rem 2rem;
    position: relative;
    overflow: hidden;
}
.dark .webkernel-upgrade-page-pipeline-section {
    background: #0a0a0a;
}
.webkernel-upgrade-page-pipeline-noise {
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.025;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 200px;
}
.webkernel-upgrade-page-pipeline-inner {
    position: relative;
    z-index: 1;
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: start;
}
.webkernel-upgrade-page-pipeline-left .webkernel-upgrade-page-section-eyebrow {
    text-align: left;
    color: rgba(255,255,255,0.35);
}
.webkernel-upgrade-page-pipeline-left .webkernel-upgrade-page-section-title {
    text-align: left;
    color: #ffffff;
}
.webkernel-upgrade-page-pipeline-left .webkernel-upgrade-page-section-sub {
    text-align: left;
    color: rgba(255,255,255,0.55);
    margin: 0 0 0;
    max-width: 400px;
}
.webkernel-upgrade-page-steps {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.webkernel-upgrade-page-step {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    padding: 1.375rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    position: relative;
}
.webkernel-upgrade-page-step:last-child { border-bottom: none; }
.webkernel-upgrade-page-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.6);
    font-size: 0.65rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: var(--wk-mono);
}
.webkernel-upgrade-page-step-content { flex: 1; min-width: 0; }
.webkernel-upgrade-page-step-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.3rem;
    flex-wrap: wrap;
}
.webkernel-upgrade-page-step-icon { color: var(--wk-accent); flex-shrink: 0; }
.webkernel-upgrade-page-step-icon svg { width: 14px; height: 14px; }
.webkernel-upgrade-page-step-label {
    font-size: 0.875rem;
    font-weight: 650;
    color: #ffffff;
}
.webkernel-upgrade-page-step-badge {
    font-family: var(--wk-mono);
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    padding: 2px 7px;
    border-radius: 3px;
    border: 1px solid var(--wk-accent-border);
    color: var(--wk-accent);
    background: var(--wk-accent-dim);
}
.webkernel-upgrade-page-step-desc {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.45);
    line-height: 1.65;
    margin: 0;
}

/* ══════════════════════════════════════════════════════════════
   TRUSTED BY SECTION
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-trusted-section {
    background: var(--wk-surface-card);
    border-bottom: 1px solid var(--wk-border);
    padding: 3.5rem 2rem;
}
.dark .webkernel-upgrade-page-trusted-section {
    background: var(--wk-surface);
}
.webkernel-upgrade-page-trusted-label {
    text-align: center;
    font-family: var(--wk-mono);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--wk-text-faint);
    margin-bottom: 2rem;
}
.webkernel-upgrade-page-trusted-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.625rem;
    max-width: 840px;
    margin: 0 auto;
}
.webkernel-upgrade-page-trusted-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid var(--wk-border);
    border-radius: 99px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--wk-text-muted);
    background: var(--wk-surface-raised);
    transition: border-color 0.15s, color 0.15s;
    cursor: default;
}
.webkernel-upgrade-page-trusted-chip:hover {
    border-color: var(--wk-accent-border);
    color: var(--wk-text);
}
.webkernel-upgrade-page-trusted-chip-emoji { font-size: 1rem; }

/* ══════════════════════════════════════════════════════════════
   TWO COL — Stack + Philosophy
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-two-col-section {
    background: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
    padding: 5rem 2rem;
}
.webkernel-upgrade-page-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: start;
    max-width: 1080px;
    margin: 0 auto;
}
.webkernel-upgrade-page-card {
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
    background: var(--wk-surface-card);
}
.dark .webkernel-upgrade-page-card {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-card-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--wk-border);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.webkernel-upgrade-page-card-head-icon {
    width: 30px;
    height: 30px;
    border-radius: var(--wk-radius);
    background: var(--wk-accent-dim);
    border: 1px solid var(--wk-accent-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--wk-accent);
    flex-shrink: 0;
}
.webkernel-upgrade-page-card-head-icon svg { width: 14px; height: 14px; }
.webkernel-upgrade-page-card-head-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--wk-text);
    margin: 0;
    letter-spacing: -0.02em;
}
.webkernel-upgrade-page-card-body {
    padding: 1.25rem 1.5rem;
}
.webkernel-upgrade-page-stack-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--wk-border);
}
.webkernel-upgrade-page-stack-row:last-child { border-bottom: none; }
.webkernel-upgrade-page-stack-key {
    font-family: var(--wk-mono);
    font-size: 0.775rem;
    color: var(--wk-text-muted);
}
.webkernel-upgrade-page-stack-val {
    font-family: var(--wk-mono);
    font-size: 0.775rem;
    font-weight: 600;
    color: var(--wk-text);
}
.webkernel-upgrade-page-quote {
    border-left: 2px solid var(--wk-accent);
    padding: 1rem 1.125rem;
    background: var(--wk-accent-dim);
    border-radius: 0 var(--wk-radius) var(--wk-radius) 0;
    margin-bottom: 1rem;
}
.webkernel-upgrade-page-quote-text {
    font-size: 0.9rem;
    font-style: italic;
    color: var(--wk-text);
    line-height: 1.75;
    margin: 0 0 0.5rem;
}
.webkernel-upgrade-page-quote-attr {
    font-family: var(--wk-mono);
    font-size: 0.65rem;
    color: var(--wk-text-faint);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin: 0;
}
.webkernel-upgrade-page-philosophy-body {
    font-size: 0.8125rem;
    color: var(--wk-text-muted);
    line-height: 1.8;
    margin: 0 0 1rem;
}
.webkernel-upgrade-page-philosophy-strong {
    color: var(--wk-text);
    font-weight: 600;
}
.webkernel-upgrade-page-nm-card {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.875rem 1rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-nm-logo {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    background: var(--wk-accent-dim);
    border: 1px solid var(--wk-accent-border);
    border-radius: var(--wk-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--wk-accent);
}
.webkernel-upgrade-page-nm-logo svg { width: 16px; height: 16px; }
.webkernel-upgrade-page-nm-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--wk-text);
    margin: 0 0 2px;
}
.webkernel-upgrade-page-nm-sub {
    font-size: 0.72rem;
    color: var(--wk-text-muted);
    margin: 0;
}

/* ══════════════════════════════════════════════════════════════
   VIDEO SECTION
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-video-section {
    background: var(--wk-surface-card);
    border-bottom: 1px solid var(--wk-border);
    padding: 5rem 2rem;
}
.dark .webkernel-upgrade-page-video-section {
    background: var(--wk-surface);
}
.webkernel-upgrade-page-video-wrap {
    max-width: 840px;
    margin: 0 auto;
}
.webkernel-upgrade-page-video-box {
    position: relative;
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
    aspect-ratio: 16 / 9;
    border: 1px solid var(--wk-border);
    background: var(--wk-surface-raised);
    cursor: pointer;
    box-shadow: 0 24px 80px rgba(0,0,0,0.08);
}
.dark .webkernel-upgrade-page-video-box {
    box-shadow: 0 24px 80px rgba(0,0,0,0.4);
}
.webkernel-upgrade-page-video-box iframe {
    width: 100%; height: 100%; border: none; display: block;
}
.webkernel-upgrade-page-video-thumb {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 0.75rem;
}
.webkernel-upgrade-page-video-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.38);
}
.webkernel-upgrade-page-video-play {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--wk-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 40px var(--wk-accent-glow);
    position: relative;
    z-index: 1;
    transition: transform 0.2s, box-shadow 0.2s;
}
.webkernel-upgrade-page-video-play:hover {
    transform: scale(1.1);
    box-shadow: 0 16px 64px var(--wk-accent-glow);
}
.webkernel-upgrade-page-video-play svg {
    width: 24px; height: 24px;
    color: white;
    margin-left: 4px;
}
.webkernel-upgrade-page-video-caption {
    font-family: var(--wk-mono);
    font-size: 0.72rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.7);
    position: relative;
    z-index: 1;
}

/* ══════════════════════════════════════════════════════════════
   DOC LINKS
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-docs-section {
    background: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
    padding: 5rem 2rem;
}
.webkernel-upgrade-page-doc-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    max-width: 1080px;
    margin: 0 auto;
}
.webkernel-upgrade-page-doc-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.125rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    background: var(--wk-surface-card);
    text-decoration: none;
    color: var(--wk-text);
    font-size: 0.875rem;
    font-weight: 500;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
    position: relative;
    overflow: hidden;
}
.dark .webkernel-upgrade-page-doc-link {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-doc-link::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--wk-accent-dim);
    opacity: 0;
    transition: opacity 0.15s;
}
.webkernel-upgrade-page-doc-link:hover {
    border-color: var(--wk-accent-border);
    box-shadow: 0 0 0 3px var(--wk-accent-dim);
}
.webkernel-upgrade-page-doc-link:hover::before { opacity: 1; }
.webkernel-upgrade-page-doc-link-icon {
    color: var(--wk-text-muted);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    transition: color 0.15s;
}
.webkernel-upgrade-page-doc-link:hover .webkernel-upgrade-page-doc-link-icon {
    color: var(--wk-accent);
}
.webkernel-upgrade-page-doc-link-icon svg { width: 16px; height: 16px; }
.webkernel-upgrade-page-doc-link-text {
    flex: 1;
    position: relative;
    z-index: 1;
}
.webkernel-upgrade-page-doc-link-arrow {
    opacity: 0.3;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    transition: opacity 0.15s, transform 0.15s;
}
.webkernel-upgrade-page-doc-link:hover .webkernel-upgrade-page-doc-link-arrow {
    opacity: 0.7;
    transform: translate(2px, -2px);
}
.webkernel-upgrade-page-doc-link-arrow svg { width: 12px; height: 12px; }

/* ══════════════════════════════════════════════════════════════
   ADVANCED / DANGER
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-advanced-section {
    background: var(--wk-surface-card);
    padding: 2rem 2rem 3rem;
}
.dark .webkernel-upgrade-page-advanced-section {
    background: var(--wk-surface);
}
.webkernel-upgrade-page-advanced-inner {
    max-width: 1080px;
    margin: 0 auto;
}
.webkernel-upgrade-page-danger-box {
    border: 1px solid var(--wk-danger-border);
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
}
.webkernel-upgrade-page-danger-head {
    padding: 1.125rem 1.5rem;
    border-bottom: 1px solid var(--wk-danger-border);
    background: var(--wk-danger-dim);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.webkernel-upgrade-page-danger-head-icon { color: var(--wk-danger); }
.webkernel-upgrade-page-danger-head-icon svg { width: 16px; height: 16px; }
.webkernel-upgrade-page-danger-head-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--wk-danger);
    margin: 0;
}
.webkernel-upgrade-page-danger-head-desc {
    font-size: 0.775rem;
    color: color-mix(in oklch, var(--wk-danger) 70%, var(--wk-text-muted));
    margin: 0;
}
.webkernel-upgrade-page-danger-body {
    padding: 1.25rem 1.5rem;
    background: var(--wk-surface-card);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
}
.dark .webkernel-upgrade-page-danger-body {
    background: var(--wk-surface-raised);
}

/* ══════════════════════════════════════════════════════════════
   ROLLBACK PANEL
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 9900;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 1.5rem;
}
.webkernel-upgrade-page-panel {
    width: 500px;
    max-width: 95vw;
    max-height: 88vh;
    background: var(--wk-surface-card);
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-xl);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(0,0,0,0.18);
}
.dark .webkernel-upgrade-page-panel {
    background: var(--wk-surface-raised);
    box-shadow: 0 32px 80px rgba(0,0,0,0.6);
}
.webkernel-upgrade-page-panel-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--wk-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.webkernel-upgrade-page-panel-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--wk-text);
    margin: 0 0 2px;
    letter-spacing: -0.02em;
}
.webkernel-upgrade-page-panel-subtitle {
    font-size: 0.72rem;
    color: var(--wk-text-muted);
    font-family: var(--wk-mono);
    letter-spacing: 0.04em;
    margin: 0;
}
.webkernel-upgrade-page-panel-close {
    background: none;
    border: none;
    color: var(--wk-text-muted);
    cursor: pointer;
    padding: 5px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    transition: color 0.12s, background 0.12s;
}
.webkernel-upgrade-page-panel-close:hover {
    color: var(--wk-text);
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-panel-close svg { width: 18px; height: 18px; }
.webkernel-upgrade-page-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem 1.5rem;
}
.webkernel-upgrade-page-rollback-warning {
    background: var(--wk-warning-dim);
    border: 1px solid var(--wk-warning-border);
    border-left: 3px solid var(--wk-warning);
    padding: 0.875rem 1rem;
    font-size: 0.8rem;
    color: var(--wk-warning);
    border-radius: 0 var(--wk-radius) var(--wk-radius) 0;
    margin-bottom: 1.25rem;
    line-height: 1.65;
}
.webkernel-upgrade-page-release {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding: 1rem 1.125rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    margin-bottom: 0.625rem;
    background: var(--wk-surface-raised);
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.webkernel-upgrade-page-release.is-current {
    border-color: var(--wk-accent-border);
    background: var(--wk-accent-dim);
}
.webkernel-upgrade-page-release:hover { border-color: var(--wk-accent-border); }
.webkernel-upgrade-page-release-meta { flex: 1; min-width: 0; }
.webkernel-upgrade-page-release-head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.2rem;
    flex-wrap: wrap;
}
.webkernel-upgrade-page-release-ver {
    font-family: var(--wk-mono);
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--wk-text);
    margin: 0;
}
.webkernel-upgrade-page-release-date {
    font-family: var(--wk-mono);
    font-size: 0.68rem;
    color: var(--wk-text-faint);
    letter-spacing: 0.06em;
    margin: 0 0 0.375rem;
}
.webkernel-upgrade-page-release-notes {
    font-size: 0.8rem;
    color: var(--wk-text-muted);
    line-height: 1.55;
    margin: 0;
}
.webkernel-upgrade-page-release-radio {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid var(--wk-border);
    flex-shrink: 0;
    margin-top: 2px;
    transition: all 0.15s;
}
.webkernel-upgrade-page-panel-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--wk-border);
    display: flex;
    justify-content: flex-end;
    gap: 0.625rem;
    flex-shrink: 0;
}

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════════════════ */
@media (max-width: 900px) {
    .webkernel-upgrade-page-pipeline-inner { grid-template-columns: 1fr; gap: 2.5rem; }
    .webkernel-upgrade-page-stats          { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-features-grid  { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-two-col        { grid-template-columns: 1fr; }
    .webkernel-upgrade-page-doc-grid       { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-feature:nth-child(3n)  { border-right: 1px solid var(--wk-border); }
    .webkernel-upgrade-page-feature:nth-child(2n)  { border-right: none; }
}
@media (max-width: 600px) {
    .webkernel-upgrade-page-hero           { padding: 3.5rem 1.25rem 3rem; }
    .webkernel-upgrade-page-features-grid  { grid-template-columns: 1fr; }
    .webkernel-upgrade-page-feature        { border-right: none !important; }
    .webkernel-upgrade-page-doc-grid       { grid-template-columns: 1fr; }
    .webkernel-upgrade-page-stats          { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-hero-cta       { flex-direction: column; align-items: center; }
}
</style>

{{-- ══════════════════════════════════════════════════════════════════════
     PAGE ROOT
══════════════════════════════════════════════════════════════════════ --}}
<div
    class="webkernel-upgrade-page-root"
    x-data="{
        rollbackOpen: false,
        selectedRelease: null,
        videoPlaying: false,
        openRollback()  { this.rollbackOpen = true; },
        closeRollback() { this.rollbackOpen = false; this.selectedRelease = null; },
        selectRelease(ver) { this.selectedRelease = (this.selectedRelease === ver) ? null : ver; },
        playVideo() { this.videoPlaying = true; }
    }"
>

{{-- ────────────────────────────────────────────────────────────────────
     ANNOUNCEMENT BAR
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-announce">
    <span class="webkernel-upgrade-page-announce-badge">NEW</span>
    <span>Webkernel v{{ $latestVersion }} is available — upgrade in one click</span>
    <span class="webkernel-upgrade-page-announce-arrow">→</span>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     HERO
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-hero">
    <div class="webkernel-upgrade-page-hero-grid"></div>
    <div class="webkernel-upgrade-page-hero-glow"></div>
    <div class="webkernel-upgrade-page-hero-inner">
        <div class="webkernel-upgrade-page-eyebrow">
            <span class="webkernel-upgrade-page-eyebrow-dot"></span>
            Webkernel Core — Upgrade Center
        </div>
        <h1 class="webkernel-upgrade-page-hero-title">
            Makes The Web
            <span class="webkernel-upgrade-page-hero-title-muted"><br>Easier</span>
            <span class="webkernel-upgrade-page-hero-title-accent">.</span>
        </h1>
        <p class="webkernel-upgrade-page-hero-sub">
            Sovereign, performance-obsessed and modular. Every upgrade is cryptographically signed,
            atomically applied and fully reversible — with zero data loss.
        </p>
        <div class="webkernel-upgrade-page-hero-cta">
            @if(!$isUpToDate)
                <x-filament::button
                    color="primary"
                    icon="heroicon-m-rocket-launch"
                    size="lg"
                    wire:click="updateKernel"
                >
                    Upgrade to v{{ $latestVersion }}
                </x-filament::button>
            @else
                <x-filament::button
                    color="success"
                    icon="heroicon-m-check-circle"
                    size="lg"
                    disabled
                >
                    Kernel is Up to Date
                </x-filament::button>
            @endif
            <x-filament::button
                color="gray"
                icon="heroicon-m-arrow-path"
                size="lg"
                outlined
                wire:click="$dispatch('wk-check-updates')"
            >
                Check for Upgrades
            </x-filament::button>
        </div>
        <div class="webkernel-upgrade-page-hero-badges">
            @if(!$isUpToDate)
                <x-filament::badge color="warning" icon="heroicon-m-arrow-up-circle">
                    v{{ $latestVersion }} available
                </x-filament::badge>
            @else
                <x-filament::badge color="success" icon="heroicon-m-check-circle">
                    Up to date
                </x-filament::badge>
            @endif
            <x-filament::badge color="gray">
                {{ $currentChannel }}
            </x-filament::badge>
            <span class="webkernel-upgrade-page-hero-checked">
                Checked {{ $lastChecked }}
            </span>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     RUNTIME STATS BAR
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-stats">
    <div class="webkernel-upgrade-page-stat">
        <div class="webkernel-upgrade-page-stat-val">{{ $phpVersion }}</div>
        <div class="webkernel-upgrade-page-stat-label">PHP Runtime</div>
    </div>
    <div class="webkernel-upgrade-page-stat">
        <div class="webkernel-upgrade-page-stat-val">{{ $laravelVersion }}</div>
        <div class="webkernel-upgrade-page-stat-label">Laravel</div>
    </div>
    <div class="webkernel-upgrade-page-stat">
        <div class="webkernel-upgrade-page-stat-val">{{ $filamentVersion }}</div>
        <div class="webkernel-upgrade-page-stat-label">Filament</div>
    </div>
    <div class="webkernel-upgrade-page-stat">
        <div
            class="webkernel-upgrade-page-stat-val"
            style="color: {{ $isUpToDate ? 'var(--wk-success)' : 'var(--wk-warning)' }};"
        >{{ $isUpToDate ? 'OK' : 'PENDING' }}</div>
        <div class="webkernel-upgrade-page-stat-label">Kernel Status</div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     UPDATE STATUS (reactive)
────────────────────────────────────────────────────────────────────── --}}
@if(!empty($updateStatus) || !empty($updateError))
<div class="webkernel-upgrade-page-status-wrap">
    <div class="webkernel-upgrade-page-status-inner">
        @if(!empty($updateStatus))
        <div class="webkernel-upgrade-page-status-strip">
            <div class="webkernel-upgrade-page-pulse-dot" style="background: var(--primary-500);"></div>
            <div style="flex:1;">
                <span class="webkernel-upgrade-page-status-text">{{ $updateStatus }}</span>
                <div class="webkernel-upgrade-page-progress-track">
                    <div class="webkernel-upgrade-page-progress-fill" style="width: {{ $isUpdating ? '60%' : '100%' }};"></div>
                </div>
            </div>
        </div>
        @endif
        @if(!empty($updateError))
        <div class="webkernel-upgrade-page-status-strip" style="border-color: var(--wk-danger-border); margin-top: 0.625rem;">
            <div class="webkernel-upgrade-page-pulse-dot" style="background: var(--danger-500);"></div>
            <div style="flex:1;">
                <span class="webkernel-upgrade-page-status-text" style="font-weight:600;">Upgrade Failed</span>
                <div class="webkernel-upgrade-page-error-block">{{ $updateError }}</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ────────────────────────────────────────────────────────────────────
     WHAT WEBKERNEL DELIVERS — Features
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-features-section">
    <div class="webkernel-upgrade-page-section-eyebrow">What's in Webkernel</div>
    <h2 class="webkernel-upgrade-page-section-title">
        Everything you need to build<br>great products on the web.
    </h2>
    <p class="webkernel-upgrade-page-section-sub">
        A sovereign, performance-optimized foundation built on Laravel, Filament and Livewire.
        Yours to own, deploy and extend.
    </p>
    <div class="webkernel-upgrade-page-features-grid">
        @foreach($features as $feat)
            <div class="webkernel-upgrade-page-feature">
                <div class="webkernel-upgrade-page-feature-icon">
                    <x-filament::icon :icon="$feat['icon']" />
                </div>
                <h3 class="webkernel-upgrade-page-feature-title">{{ $feat['title'] }}</h3>
                <p class="webkernel-upgrade-page-feature-body">{{ $feat['body'] }}</p>
            </div>
        @endforeach
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     UPGRADE PIPELINE — dark section
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-pipeline-section">
    <div class="webkernel-upgrade-page-pipeline-noise"></div>
    <div class="webkernel-upgrade-page-pipeline-inner">
        <div class="webkernel-upgrade-page-pipeline-left">
            <div class="webkernel-upgrade-page-section-eyebrow">Upgrade Pipeline</div>
            <h2 class="webkernel-upgrade-page-section-title">
                Built on a foundation of<br>safe, atomic upgrades.
            </h2>
            <p class="webkernel-upgrade-page-section-sub">
                Each step executes sequentially. The process rolls back automatically on failure —
                your system is never left in a broken state.
            </p>
        </div>
        <div class="webkernel-upgrade-page-steps">
            @foreach($steps as $index => $step)
                <div class="webkernel-upgrade-page-step">
                    <div class="webkernel-upgrade-page-step-num">{{ $index + 1 }}</div>
                    <div class="webkernel-upgrade-page-step-content">
                        <div class="webkernel-upgrade-page-step-head">
                            <span class="webkernel-upgrade-page-step-icon">
                                <x-filament::icon :icon="$step['icon']" />
                            </span>
                            <span class="webkernel-upgrade-page-step-label">{{ $step['label'] }}</span>
                            <span class="webkernel-upgrade-page-step-badge">{{ $step['badge'] }}</span>
                        </div>
                        <p class="webkernel-upgrade-page-step-desc">{{ $step['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     TRUSTED BY
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-trusted-section">
    <div class="webkernel-upgrade-page-trusted-label">Trusted across industries</div>
    <div class="webkernel-upgrade-page-trusted-grid">
        @foreach($usedBy as $sector)
            <div class="webkernel-upgrade-page-trusted-chip">
                <span class="webkernel-upgrade-page-trusted-chip-emoji">{{ $sector['emoji'] }}</span>
                {{ $sector['label'] }}
            </div>
        @endforeach
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     VIDEO WALKTHROUGH
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-video-section">
    <div class="webkernel-upgrade-page-section-eyebrow">Platform Walkthrough</div>
    <h2 class="webkernel-upgrade-page-section-title">See Webkernel in action.</h2>
    <p class="webkernel-upgrade-page-section-sub">
        Upgrade pipeline, module system, and multi-panel architecture — all demonstrated live.
    </p>
    <div class="webkernel-upgrade-page-video-wrap">
        <div class="webkernel-upgrade-page-video-box">
            <template x-if="!videoPlaying">
                <div
                    class="webkernel-upgrade-page-video-thumb"
                    style="background-image: url('https://img.youtube.com/vi/{{ $videoId }}/maxresdefault.jpg');"
                    x-on:click="playVideo()"
                >
                    <div class="webkernel-upgrade-page-video-overlay"></div>
                    <div class="webkernel-upgrade-page-video-play">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <span class="webkernel-upgrade-page-video-caption">Watch walkthrough</span>
                </div>
            </template>
            <template x-if="videoPlaying">
                <iframe
                    :src="`https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&rel=0&modestbranding=1`"
                    allow="autoplay; encrypted-media; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </template>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     TECH STACK + PHILOSOPHY
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-two-col-section">
    <div class="webkernel-upgrade-page-two-col">
        {{-- Stack card --}}
        <div class="webkernel-upgrade-page-card">
            <div class="webkernel-upgrade-page-card-head">
                <div class="webkernel-upgrade-page-card-head-icon">
                    <x-filament::icon icon="heroicon-o-code-bracket" />
                </div>
                <h3 class="webkernel-upgrade-page-card-head-title">Tech Stack</h3>
            </div>
            <div class="webkernel-upgrade-page-card-body">
                @foreach($stack as $row)
                    <div class="webkernel-upgrade-page-stack-row">
                        <span class="webkernel-upgrade-page-stack-key">{{ $row['label'] }}</span>
                        <span class="webkernel-upgrade-page-stack-val">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Philosophy + Numerimondes --}}
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="webkernel-upgrade-page-card">
                <div class="webkernel-upgrade-page-card-head">
                    <div class="webkernel-upgrade-page-card-head-icon">
                        <x-filament::icon icon="heroicon-o-light-bulb" />
                    </div>
                    <h3 class="webkernel-upgrade-page-card-head-title">Philosophy</h3>
                </div>
                <div class="webkernel-upgrade-page-card-body">
                    <div class="webkernel-upgrade-page-quote">
                        <p class="webkernel-upgrade-page-quote-text">
                            "Software should be a reliable, automated workforce under your direct command — free from the restrictive roadmaps or pricing models of major cloud conglomerates."
                        </p>
                        <p class="webkernel-upgrade-page-quote-attr">
                            Yassine El Moumen &middot; Founder, Numerimondes &middot; Casablanca
                        </p>
                    </div>
                    <p class="webkernel-upgrade-page-philosophy-body">
                        Webkernel is built on the principle of
                        <strong class="webkernel-upgrade-page-philosophy-strong">digital sovereignty</strong>.
                        Every organization deserves software that serves them — not a vendor's quarterly targets.
                    </p>
                </div>
            </div>
            <div class="webkernel-upgrade-page-nm-card">
                <div class="webkernel-upgrade-page-nm-logo">
                    <x-filament::icon icon="heroicon-o-building-office-2" />
                </div>
                <div style="flex:1; min-width:0;">
                    <p class="webkernel-upgrade-page-nm-name">Numerimondes</p>
                    <p class="webkernel-upgrade-page-nm-sub">Registered company &middot; Casablanca, Morocco</p>
                </div>
                <x-filament::badge color="gray">EPL 2.0</x-filament::badge>
            </div>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     DOCUMENTATION LINKS
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-docs-section">
    <div class="webkernel-upgrade-page-section-eyebrow">Resources</div>
    <h2 class="webkernel-upgrade-page-section-title">Documentation &amp; Resources</h2>
    <p class="webkernel-upgrade-page-section-sub">Official guides, API reference, marketplace and community links.</p>
    <div class="webkernel-upgrade-page-doc-grid">
        @foreach($docLinks as $link)
            <a
                href="{{ $link['url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="webkernel-upgrade-page-doc-link"
            >
                <span class="webkernel-upgrade-page-doc-link-icon">
                    <x-filament::icon :icon="$link['icon']" />
                </span>
                <span class="webkernel-upgrade-page-doc-link-text">{{ $link['label'] }}</span>
                <span class="webkernel-upgrade-page-doc-link-arrow">
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" />
                </span>
            </a>
        @endforeach
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     ADVANCED / DANGER ZONE
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-advanced-section">
    <div class="webkernel-upgrade-page-advanced-inner">
        <div class="webkernel-upgrade-page-danger-box">
            <div class="webkernel-upgrade-page-danger-head">
                <span class="webkernel-upgrade-page-danger-head-icon">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" />
                </span>
                <div>
                    <p class="webkernel-upgrade-page-danger-head-title">Advanced Options</p>
                    <p class="webkernel-upgrade-page-danger-head-desc">Destructive operations — each requires explicit confirmation.</p>
                </div>
            </div>
            <div class="webkernel-upgrade-page-danger-body">
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-uturn-left"
                    outlined
                    x-on:click="openRollback()"
                >
                    Rollback to Previous Version
                </x-filament::button>
                <x-filament::button
                    color="danger"
                    icon="heroicon-m-trash"
                    outlined
                    wire:click="forceResetKernel"
                >
                    Force Reset Kernel
                </x-filament::button>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
