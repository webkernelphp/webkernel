<x-filament-panels::page>

@if($this->isProcessing)
    @include('webkernel-system::filament.pages.processing-upgrade')
    @php return; @endphp
@endif

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

    // Fallbacks for fresh instances that haven't fetched tags yet
    $hasTagData = count($features) > 0;
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
    REMOVING PAGE PADDING AND HEADER ACTIONS (KEEP AS IS)
══════════════════════════════════════════════════════════════════════ --}}
<style>
    .fi-main, .fi-page-header-main-ctn, .fi-header {
        padding: 0 !important;
        row-gap: calc(var(--spacing) * 0) !important;
    }
    .fi-header{
        display: none;
    }
</style>

{{-- ══════════════════════════════════════════════════════════════════════
     PREMIUM SAAS STYLES – SWISS DESIGN PRINCIPLES
══════════════════════════════════════════════════════════════════════ --}}
<style>
/* Google Fonts — clean Swiss typography */
@import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:wght@400;500&display=swap');

/* Design Tokens */
.webkernel-upgrade-page-root {
    --wk-accent:          var(--primary-600, #6d28d9);
    --wk-accent-dim:      color-mix(in oklch, var(--wk-accent) 8%, transparent);
    --wk-accent-border:   color-mix(in oklch, var(--wk-accent) 20%, transparent);
    --wk-accent-glow:     color-mix(in oklch, var(--wk-accent) 25%, transparent);
    --wk-surface:         var(--gray-50, #fafafa);
    --wk-surface-raised:  #ffffff;
    --wk-surface-card:    #ffffff;
    --wk-border:          color-mix(in oklch, var(--gray-400) 20%, transparent);
    --wk-text:            var(--gray-900, #0a0a0a);
    --wk-text-muted:      var(--gray-600, #525252);
    --wk-text-faint:      var(--gray-400, #a3a3a3);
    --wk-mono:            'JetBrains Mono', 'SF Mono', ui-monospace, monospace;
    --wk-sans:            'Inter', system-ui, -apple-system, sans-serif;
    --wk-radius:          var(--radius-lg);
    --wk-radius-lg:       var(--radius-lg);
    --wk-radius-xl:       var(--radius-lg);
    --wk-success:         var(--success-600, #16a34a);
    --wk-warning:         var(--warning-600, #d97706);
    --wk-danger:          var(--danger-600, #dc2626);
    --wk-danger-dim:      color-mix(in oklch, var(--wk-danger) 6%, transparent);
    --wk-danger-border:   color-mix(in oklch, var(--wk-danger) 20%, transparent);
    --wk-warning-dim:     color-mix(in oklch, var(--wk-warning) 6%, transparent);
    --wk-warning-border:  color-mix(in oklch, var(--wk-warning) 20%, transparent);
    --wk-shadow-sm:       0 4px 6px -2px rgb(0 0 0 / 0.03), 0 2px 4px -1px rgb(0 0 0 / 0.02);
    --wk-shadow-md:       0 12px 24px -8px rgb(0 0 0 / 0.06), 0 4px 8px -2px rgb(0 0 0 / 0.02);
    --wk-shadow-lg:       0 24px 48px -12px rgb(0 0 0 / 0.08), 0 8px 16px -4px rgb(0 0 0 / 0.02);
    --wk-transition:      all 0.25s cubic-bezier(0.16, 1, 0.3, 1);

    display: flex;
    flex-direction: column;
    background: var(--wk-surface);
    font-family: var(--wk-sans);
    color: var(--wk-text);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.dark .webkernel-upgrade-page-root {
    --wk-surface:        #0c0c0c;
    --wk-surface-raised: #141414;
    --wk-surface-card:   #1a1a1a;
    --wk-border:         #2a2a2a;
    --wk-text:           #ededed;
    --wk-text-muted:     #a1a1a1;
    --wk-text-faint:     #5a5a5a;
    --wk-shadow-sm:      0 4px 6px -2px rgb(0 0 0 / 0.2);
    --wk-shadow-md:      0 12px 24px -8px rgb(0 0 0 / 0.3);
    --wk-shadow-lg:      0 24px 48px -12px rgb(0 0 0 / 0.4);
}

.webkernel-upgrade-page-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
    width: 100%;
}

/* ══════════════════════════════════════════════════════════════
   SECTION SPACING
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-section {
    padding: 6rem 2rem;
    border-bottom: 1px solid var(--wk-border);
    background: var(--wk-surface);
}
.webkernel-upgrade-page-section-alt {
    background: var(--wk-surface-raised);
}

/* ══════════════════════════════════════════════════════════════
   TOP ANNOUNCEMENT BAR
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-announce {
    background: var(--wk-text);
    color: var(--wk-surface);
    padding: 0.75rem 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    font-size: 0.875rem;
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
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    padding: 0.25rem 0.75rem;
    border-radius: 100px;
    flex-shrink: 0;
}
.webkernel-upgrade-page-announce-arrow {
    opacity: 0.6;
    font-size: 1rem;
    transition: transform 0.2s;
}
.webkernel-upgrade-page-announce:hover .webkernel-upgrade-page-announce-arrow {
    transform: translateX(4px);
}

/* ══════════════════════════════════════════════════════════════
   HERO SECTION — refined, more airy
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-hero {
    position: relative;
    padding: 7rem 2rem 6rem;
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
        linear-gradient(var(--wk-border) 1px, transparent 1px),
        linear-gradient(90deg, var(--wk-border) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 80% 50% at 50% 0%, black 30%, transparent 90%);
    opacity: 0.4;
}
.webkernel-upgrade-page-hero-glow {
    position: absolute;
    top: -150px;
    left: 50%;
    transform: translateX(-50%);
    width: 900px;
    height: 600px;
    background: radial-gradient(ellipse at center, var(--wk-accent-glow) 0%, transparent 70%);
    pointer-events: none;
    filter: blur(40px);
    opacity: 0.6;
}
.webkernel-upgrade-page-hero-inner {
    position: relative;
    z-index: 2;
    max-width: 820px;
    margin: 0 auto;
}
.webkernel-upgrade-page-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--wk-border);
    background: var(--wk-surface-raised);
    border-radius: 100px;
    padding: 0.375rem 1rem 0.375rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.02em;
    color: var(--wk-text-muted);
    margin-bottom: 2rem;
    font-family: var(--wk-mono);
    box-shadow: var(--wk-shadow-sm);
}
.webkernel-upgrade-page-eyebrow-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--wk-accent);
    display: block;
    flex-shrink: 0;
    animation: webkernel-upgrade-page-blink 2.5s ease-in-out infinite;
}
@keyframes webkernel-upgrade-page-blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.4; transform: scale(0.9); }
}
.webkernel-upgrade-page-hero-title {
    font-size: clamp(3rem, 8vw, 5rem);
    color: var(--wk-text);
    line-height: 1.05;
    letter-spacing: -0.04em;
    margin: 0 0 1.5rem;
    font-family: var(--wk-sans);
    text-wrap: balance;
}
.webkernel-upgrade-page-hero-title-accent {
    color: var(--wk-accent);
    background: linear-gradient(145deg, var(--wk-accent), color-mix(in oklch, var(--wk-accent) 70%, #fff));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.webkernel-upgrade-page-hero-sub {
    font-size: 1.125rem;
    color: var(--wk-text-muted);
    line-height: 1.7;
    max-width: 620px;
    margin: 0 auto 2.5rem;
    font-weight: 400;
    text-wrap: balance;
}
.webkernel-upgrade-page-hero-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2.5rem;
}
.webkernel-upgrade-page-hero-badges {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.webkernel-upgrade-page-hero-checked {
    font-size: 0.75rem;
    color: var(--wk-text-faint);
    font-family: var(--wk-mono);
    letter-spacing: 0.03em;
}

/* ══════════════════════════════════════════════════════════════
   RUNTIME STATS TICKER — Swiss grid
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-bottom: 1px solid var(--wk-border);
    background: var(--wk-surface);
}
.webkernel-upgrade-page-stat {
    padding: 2rem 1.5rem;
    border-right: 1px solid var(--wk-border);
    text-align: center;
    transition: background 0.15s;
}
.webkernel-upgrade-page-stat:last-child { border-right: none; }
.webkernel-upgrade-page-stat:hover {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-stat-val {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--wk-text);
    letter-spacing: -0.03em;
    line-height: 1.1;
    margin-bottom: 0.5rem;
    font-family: var(--wk-mono);
}
.webkernel-upgrade-page-stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--wk-text-faint);
    font-family: var(--wk-mono);
}

/* ══════════════════════════════════════════════════════════════
   UPDATE STATUS STRIP — refined
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-status-wrap {
    padding: 1.5rem 2rem;
    background: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
}
.webkernel-upgrade-page-status-inner {
    max-width: 1280px;
    margin: 0 auto;
}
.webkernel-upgrade-page-status-strip {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1rem 1.5rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    background: var(--wk-surface-card);
    box-shadow: var(--wk-shadow-sm);
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-status-strip:hover {
    box-shadow: var(--wk-shadow-md);
}
.dark .webkernel-upgrade-page-status-strip {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-pulse-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    animation: webkernel-upgrade-page-pulse 2s cubic-bezier(.4,0,.6,1) infinite;
}
@keyframes webkernel-upgrade-page-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(0.9); }
}
.webkernel-upgrade-page-status-text {
    font-size: 0.9375rem;
    color: var(--wk-text);
    flex: 1;
    font-family: var(--wk-mono);
    font-weight: 500;
}
.webkernel-upgrade-page-progress-track {
    height: 3px;
    background: var(--wk-border);
    border-radius: 100px;
    overflow: hidden;
    margin-top: 0.75rem;
}
.webkernel-upgrade-page-progress-fill {
    height: 100%;
    background: var(--wk-accent);
    border-radius: 100px;
    transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.webkernel-upgrade-page-error-block {
    background: var(--wk-danger-dim);
    border: 1px solid var(--wk-danger-border);
    border-left: 4px solid var(--wk-danger);
    border-radius: 0 var(--wk-radius) var(--wk-radius) 0;
    padding: 0.875rem 1.25rem;
    font-size: 0.875rem;
    color: var(--wk-danger);
    font-family: var(--wk-mono);
    margin-top: 0.75rem;
}

/* ══════════════════════════════════════════════════════════════
   FEATURES GRID — Swiss cards with hover lift
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-features-section {
    background: var(--wk-surface-raised);
    border-bottom: 1px solid var(--wk-border);
    padding: 6rem 2rem;
}
.webkernel-upgrade-page-section-eyebrow {
    text-align: center;
    font-family: var(--wk-mono);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--wk-text-faint);
    margin-bottom: 1.25rem;
}
.webkernel-upgrade-page-section-title {
    text-align: center;
    font-size: clamp(2rem, 5vw, 3rem);
    color: var(--wk-text);
    letter-spacing: -0.03em;
    line-height: 1.15;
    margin: 0 0 1rem;
    font-family: var(--wk-sans);
    text-wrap: balance;
}
.webkernel-upgrade-page-section-sub {
    text-align: center;
    font-size: 1.0625rem;
    color: var(--wk-text-muted);
    line-height: 1.7;
    max-width: 600px;
    margin: 0 auto 4rem;
    text-wrap: balance;
}
.webkernel-upgrade-page-features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.webkernel-upgrade-page-feature {
    padding: 2.5rem 2rem;
    background: var(--wk-surface-card);
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-xl);
    transition: var(--wk-transition);
    box-shadow: var(--wk-shadow-sm);
    position: relative;
    overflow: hidden;
}
.webkernel-upgrade-page-feature::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 0% 0%, var(--wk-accent-dim), transparent 70%);
    opacity: 0;
    transition: opacity 0.4s;
    pointer-events: none;
}
.webkernel-upgrade-page-feature:hover {
    transform: translateY(-4px);
    box-shadow: var(--wk-shadow-lg);
    border-color: var(--wk-accent-border);
}
.webkernel-upgrade-page-feature:hover::after {
    opacity: 0.5;
}
.webkernel-upgrade-page-feature-icon {
    width: 48px;
    height: 48px;
    background: var(--wk-accent-dim);
    border: 1px solid var(--wk-accent-border);
    border-radius: var(--wk-radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.75rem;
    color: var(--wk-accent);
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-feature:hover .webkernel-upgrade-page-feature-icon {
    background: var(--wk-accent);
    color: white;
    border-color: var(--wk-accent);
}
.webkernel-upgrade-page-feature-icon svg { width: 22px; height: 22px; }
.webkernel-upgrade-page-feature-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--wk-text);
    margin: 0 0 0.75rem;
    letter-spacing: -0.02em;
}
.webkernel-upgrade-page-feature-body {
    font-size: 0.9375rem;
    color: var(--wk-text-muted);
    line-height: 1.7;
    margin: 0;
}

/* ══════════════════════════════════════════════════════════════
   UPGRADE PIPELINE — dark section with depth
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-pipeline-section {
    background: #0a0a0a;
    color: #ffffff;
    border-bottom: 1px solid #2a2a2a;
    padding: 6rem 2rem;
    position: relative;
    overflow: hidden;
}
.dark .webkernel-upgrade-page-pipeline-section {
    background: #050505;
}
.webkernel-upgrade-page-pipeline-noise {
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.03;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 200px;
}
.webkernel-upgrade-page-pipeline-inner {
    position: relative;
    z-index: 2;
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5rem;
    align-items: start;
}
.webkernel-upgrade-page-pipeline-left .webkernel-upgrade-page-section-eyebrow {
    text-align: left;
    color: rgba(255,255,255,0.4);
}
.webkernel-upgrade-page-pipeline-left .webkernel-upgrade-page-section-title {
    text-align: left;
    color: #ffffff;
}
.webkernel-upgrade-page-pipeline-left .webkernel-upgrade-page-section-sub {
    text-align: left;
    color: rgba(255,255,255,0.6);
    margin: 0;
    max-width: 450px;
}
.webkernel-upgrade-page-steps {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.webkernel-upgrade-page-step {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
    padding: 1.75rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    transition: background 0.2s;
}
.webkernel-upgrade-page-step:last-child { border-bottom: none; }
.webkernel-upgrade-page-step:hover {
    background: rgba(255,255,255,0.02);
    margin: 0 -1rem;
    padding-left: 1rem;
    padding-right: 1rem;
    border-radius: var(--wk-radius);
}
.webkernel-upgrade-page-step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.7);
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: var(--wk-mono);
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-step:hover .webkernel-upgrade-page-step-num {
    border-color: var(--wk-accent);
    color: var(--wk-accent);
    background: rgba(109, 40, 217, 0.1);
}
.webkernel-upgrade-page-step-content { flex: 1; min-width: 0; }
.webkernel-upgrade-page-step-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}
.webkernel-upgrade-page-step-icon { color: var(--wk-accent); flex-shrink: 0; }
.webkernel-upgrade-page-step-icon svg { width: 16px; height: 16px; }
.webkernel-upgrade-page-step-label {
    font-size: 1rem;
    font-weight: 600;
    color: #ffffff;
}
.webkernel-upgrade-page-step-badge {
    font-family: var(--wk-mono);
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    padding: 0.25rem 0.75rem;
    border-radius: 100px;
    border: 1px solid var(--wk-accent-border);
    color: var(--wk-accent);
    background: var(--wk-accent-dim);
}
.webkernel-upgrade-page-step-desc {
    font-size: 0.875rem;
    color: rgba(255,255,255,0.55);
    line-height: 1.7;
    margin: 0;
}

/* ══════════════════════════════════════════════════════════════
   TRUSTED BY — clean chips
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-trusted-section {
    background: var(--wk-surface-card);
    border-bottom: 1px solid var(--wk-border);
    padding: 5rem 2rem;
}
.dark .webkernel-upgrade-page-trusted-section {
    background: var(--wk-surface);
}
.webkernel-upgrade-page-trusted-label {
    text-align: center;
    font-family: var(--wk-mono);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--wk-text-faint);
    margin-bottom: 2.5rem;
}
.webkernel-upgrade-page-trusted-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
    max-width: 900px;
    margin: 0 auto;
}
.webkernel-upgrade-page-trusted-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    border: 1px solid var(--wk-border);
    border-radius: 100px;
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--wk-text-muted);
    background: var(--wk-surface-raised);
    transition: var(--wk-transition);
    cursor: default;
    box-shadow: var(--wk-shadow-sm);
}
.webkernel-upgrade-page-trusted-chip:hover {
    border-color: var(--wk-accent-border);
    color: var(--wk-text);
    transform: translateY(-2px);
    box-shadow: var(--wk-shadow-md);
}
.webkernel-upgrade-page-trusted-chip-emoji { font-size: 1.1rem; }

/* ══════════════════════════════════════════════════════════════
   VIDEO SECTION
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-video-section {
    background: var(--wk-surface-raised);
    border-bottom: 1px solid var(--wk-border);
    padding: 6rem 2rem;
}
.webkernel-upgrade-page-video-wrap {
    max-width: 1000px;
    margin: 0 auto;
}
.webkernel-upgrade-page-video-box {
    position: relative;
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
    aspect-ratio: 16 / 9;
    border: 1px solid var(--wk-border);
    background: var(--wk-surface-card);
    cursor: pointer;
    box-shadow: var(--wk-shadow-lg);
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-video-box:hover {
    box-shadow: 0 32px 64px -12px rgb(0 0 0 / 0.15);
    transform: scale(1.01);
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
    gap: 1rem;
}
.webkernel-upgrade-page-video-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.3);
    backdrop-filter: blur(4px);
    transition: backdrop-filter 0.3s;
}
.webkernel-upgrade-page-video-box:hover .webkernel-upgrade-page-video-overlay {
    backdrop-filter: blur(6px);
}
.webkernel-upgrade-page-video-play {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--wk-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 40px var(--wk-accent-glow);
    position: relative;
    z-index: 2;
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-video-play:hover {
    transform: scale(1.15);
    background: color-mix(in oklch, var(--wk-accent) 80%, white);
}
.webkernel-upgrade-page-video-play svg {
    width: 32px; height: 32px;
    color: white;
    margin-left: 4px;
}
.webkernel-upgrade-page-video-caption {
    font-family: var(--wk-mono);
    font-size: 0.8rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.9);
    position: relative;
    z-index: 2;
    font-weight: 500;
}

/* ══════════════════════════════════════════════════════════════
   TWO COL — Stack + Philosophy (Swiss asymmetry)
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-two-col-section {
    background: var(--wk-surface);
    border-bottom: 1px solid var(--wk-border);
    padding: 6rem 2rem;
}
.webkernel-upgrade-page-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2.5rem;
    align-items: start;
    max-width: 1280px;
    margin: 0 auto;
}
.webkernel-upgrade-page-card {
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
    background: var(--wk-surface-card);
    box-shadow: var(--wk-shadow-sm);
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-card:hover {
    box-shadow: var(--wk-shadow-md);
}
.dark .webkernel-upgrade-page-card {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-card-head {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--wk-border);
    display: flex;
    align-items: center;
    gap: 1rem;
}
.webkernel-upgrade-page-card-head-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--wk-radius);
    background: var(--wk-accent-dim);
    border: 1px solid var(--wk-accent-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--wk-accent);
    flex-shrink: 0;
}
.webkernel-upgrade-page-card-head-icon svg { width: 18px; height: 18px; }
.webkernel-upgrade-page-card-head-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--wk-text);
    margin: 0;
    letter-spacing: -0.02em;
}
.webkernel-upgrade-page-card-body {
    padding: 1.75rem;
}
.webkernel-upgrade-page-stack-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 0;
    border-bottom: 1px solid var(--wk-border);
}
.webkernel-upgrade-page-stack-row:last-child { border-bottom: none; }
.webkernel-upgrade-page-stack-key {
    font-family: var(--wk-mono);
    font-size: 0.875rem;
    color: var(--wk-text-muted);
    font-weight: 500;
}
.webkernel-upgrade-page-stack-val {
    font-family: var(--wk-mono);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--wk-text);
}
.webkernel-upgrade-page-quote {
    border-left: 4px solid var(--wk-accent);
    padding: 1.25rem 1.5rem;
    background: var(--wk-accent-dim);
    border-radius: 0 var(--wk-radius-lg) var(--wk-radius-lg) 0;
    margin-bottom: 1.5rem;
}
.webkernel-upgrade-page-quote-text {
    font-size: 1rem;
    font-style: italic;
    color: var(--wk-text);
    line-height: 1.7;
    margin: 0 0 0.75rem;
}
.webkernel-upgrade-page-quote-attr {
    font-family: var(--wk-mono);
    font-size: 0.7rem;
    color: var(--wk-text-faint);
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin: 0;
}
.webkernel-upgrade-page-philosophy-body {
    font-size: 0.9375rem;
    color: var(--wk-text-muted);
    line-height: 1.8;
    margin: 0 0 1.5rem;
}
.webkernel-upgrade-page-philosophy-strong {
    color: var(--wk-text);
    font-weight: 600;
}
.webkernel-upgrade-page-nm-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    background: var(--wk-surface-raised);
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-nm-card:hover {
    border-color: var(--wk-accent-border);
}
.webkernel-upgrade-page-nm-logo {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    background: var(--wk-accent-dim);
    border: 1px solid var(--wk-accent-border);
    border-radius: var(--wk-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--wk-accent);
}
.webkernel-upgrade-page-nm-logo svg { width: 22px; height: 22px; }
.webkernel-upgrade-page-nm-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--wk-text);
    margin: 0 0 0.25rem;
}
.webkernel-upgrade-page-nm-sub {
    font-size: 0.75rem;
    color: var(--wk-text-muted);
    margin: 0;
}

/* ══════════════════════════════════════════════════════════════
   DOC LINKS — interactive grid
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-docs-section {
    background: var(--wk-surface-raised);
    border-bottom: 1px solid var(--wk-border);
    padding: 6rem 2rem;
}
.webkernel-upgrade-page-doc-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    max-width: 1280px;
    margin: 0 auto;
}
.webkernel-upgrade-page-doc-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    background: var(--wk-surface-card);
    text-decoration: none;
    color: var(--wk-text);
    font-size: 1rem;
    font-weight: 500;
    transition: var(--wk-transition);
    box-shadow: var(--wk-shadow-sm);
    backdrop-filter: blur(0);
}
.dark .webkernel-upgrade-page-doc-link {
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-doc-link:hover {
    border-color: var(--wk-accent-border);
    box-shadow: var(--wk-shadow-md);
    transform: translateY(-2px);
    background: var(--wk-accent-dim);
}
.webkernel-upgrade-page-doc-link-icon {
    color: var(--wk-text-muted);
    flex-shrink: 0;
    transition: color 0.2s;
}
.webkernel-upgrade-page-doc-link:hover .webkernel-upgrade-page-doc-link-icon {
    color: var(--wk-accent);
}
.webkernel-upgrade-page-doc-link-icon svg { width: 20px; height: 20px; }
.webkernel-upgrade-page-doc-link-text {
    flex: 1;
}
.webkernel-upgrade-page-doc-link-arrow {
    opacity: 0.4;
    flex-shrink: 0;
    transition: opacity 0.2s, transform 0.2s;
}
.webkernel-upgrade-page-doc-link:hover .webkernel-upgrade-page-doc-link-arrow {
    opacity: 1;
    transform: translate(4px, -4px);
}
.webkernel-upgrade-page-doc-link-arrow svg { width: 16px; height: 16px; }

/* ══════════════════════════════════════════════════════════════
   ADVANCED / DANGER ZONE
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-advanced-section {
    background: var(--wk-surface-card);
    padding: 3rem 2rem 4rem;
}
.dark .webkernel-upgrade-page-advanced-section {
    background: var(--wk-surface);
}
.webkernel-upgrade-page-advanced-inner {
    max-width: 1280px;
    margin: 0 auto;
}
.webkernel-upgrade-page-danger-box {
    border: 1px solid var(--wk-danger-border);
    border-radius: var(--wk-radius-xl);
    overflow: hidden;
    box-shadow: var(--wk-shadow-sm);
    background: var(--wk-surface-card);
}
.webkernel-upgrade-page-danger-head {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--wk-danger-border);
    background: var(--wk-danger-dim);
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.webkernel-upgrade-page-danger-head-icon { color: var(--wk-danger); }
.webkernel-upgrade-page-danger-head-icon svg { width: 22px; height: 22px; }
.webkernel-upgrade-page-danger-head-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--wk-danger);
    margin: 0;
}
.webkernel-upgrade-page-danger-head-desc {
    font-size: 0.875rem;
    color: color-mix(in oklch, var(--wk-danger) 80%, var(--wk-text-muted));
    margin: 0.25rem 0 0;
}
.webkernel-upgrade-page-danger-body {
    padding: 1.75rem 2rem;
    background: var(--wk-surface-card);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}
.dark .webkernel-upgrade-page-danger-body {
    background: var(--wk-surface-raised);
}

/* ══════════════════════════════════════════════════════════════
   ROLLBACK PANEL (MODAL)
══════════════════════════════════════════════════════════════ */
.webkernel-upgrade-page-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9900;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 2rem;
}
.webkernel-upgrade-page-panel {
    width: 520px;
    max-width: 95vw;
    max-height: 85vh;
    background: var(--wk-surface-card);
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-xl);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(0,0,0,0.2);
}
.dark .webkernel-upgrade-page-panel {
    background: var(--wk-surface-raised);
    box-shadow: 0 32px 80px rgba(0,0,0,0.5);
}
.webkernel-upgrade-page-panel-head {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--wk-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.webkernel-upgrade-page-panel-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--wk-text);
    margin: 0 0 0.25rem;
    letter-spacing: -0.02em;
}
.webkernel-upgrade-page-panel-subtitle {
    font-size: 0.75rem;
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
    padding: 0.5rem;
    border-radius: var(--wk-radius);
    display: flex;
    align-items: center;
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-panel-close:hover {
    color: var(--wk-text);
    background: var(--wk-surface-raised);
}
.webkernel-upgrade-page-panel-close svg { width: 20px; height: 20px; }
.webkernel-upgrade-page-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.75rem;
}
.webkernel-upgrade-page-rollback-warning {
    background: var(--wk-warning-dim);
    border: 1px solid var(--wk-warning-border);
    border-left: 4px solid var(--wk-warning);
    padding: 1rem 1.25rem;
    font-size: 0.875rem;
    color: var(--wk-warning);
    border-radius: 0 var(--wk-radius) var(--wk-radius) 0;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}
.webkernel-upgrade-page-release {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    padding: 1.25rem 1.25rem;
    border: 1px solid var(--wk-border);
    border-radius: var(--wk-radius-lg);
    margin-bottom: 0.75rem;
    background: var(--wk-surface-raised);
    cursor: pointer;
    transition: var(--wk-transition);
}
.webkernel-upgrade-page-release.is-current {
    border-color: var(--wk-accent-border);
    background: var(--wk-accent-dim);
}
.webkernel-upgrade-page-release:hover {
    border-color: var(--wk-accent-border);
    background: var(--wk-surface-card);
}
.webkernel-upgrade-page-release-meta { flex: 1; min-width: 0; }
.webkernel-upgrade-page-release-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
    flex-wrap: wrap;
}
.webkernel-upgrade-page-release-ver {
    font-family: var(--wk-mono);
    font-size: 1rem;
    font-weight: 600;
    color: var(--wk-text);
    margin: 0;
}
.webkernel-upgrade-page-release-date {
    font-family: var(--wk-mono);
    font-size: 0.7rem;
    color: var(--wk-text-faint);
    letter-spacing: 0.06em;
    margin: 0 0 0.5rem;
}
.webkernel-upgrade-page-release-notes {
    font-size: 0.875rem;
    color: var(--wk-text-muted);
    line-height: 1.6;
    margin: 0;
}
.webkernel-upgrade-page-release-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--wk-border);
    flex-shrink: 0;
    margin-top: 2px;
    transition: all 0.2s;
}
.webkernel-upgrade-page-panel-footer {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid var(--wk-border);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    flex-shrink: 0;
}

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE (MOBILE FIRST TWEAKS)
══════════════════════════════════════════════════════════════ */
@media (max-width: 900px) {
    .webkernel-upgrade-page-pipeline-inner { grid-template-columns: 1fr; gap: 3rem; }
    .webkernel-upgrade-page-stats          { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-features-grid  { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-two-col        { grid-template-columns: 1fr; }
    .webkernel-upgrade-page-doc-grid       { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-section        { padding: 4rem 1.5rem; }
}
@media (max-width: 600px) {
    .webkernel-upgrade-page-hero           { padding: 4rem 1.5rem 3rem; }
    .webkernel-upgrade-page-features-grid  { grid-template-columns: 1fr; }
    .webkernel-upgrade-page-doc-grid       { grid-template-columns: 1fr; }
    .webkernel-upgrade-page-stats          { grid-template-columns: 1fr 1fr; }
    .webkernel-upgrade-page-stat           { padding: 1.5rem 1rem; }
    .webkernel-upgrade-page-hero-cta       { flex-direction: column; align-items: stretch; }
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
        currentVersion: '{{ $currentVersion }}',
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
@if(!$isUpToDate && $latestVersion !== '')
<div class="webkernel-upgrade-page-announce">
    <span class="webkernel-upgrade-page-announce-badge">New Release</span>
    <span>Webkernel v{{ $latestVersion }} is available — upgrade in one click</span>
    <span class="webkernel-upgrade-page-announce-arrow">→</span>
</div>
@endif

{{-- ────────────────────────────────────────────────────────────────────
     HERO
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-hero">
    <div class="webkernel-upgrade-page-hero-grid"></div>
    <div class="webkernel-upgrade-page-hero-glow"></div>
    <div class="webkernel-upgrade-page-hero-inner">
        <div class="webkernel-upgrade-page-eyebrow">
            <span class="webkernel-upgrade-page-eyebrow-dot"></span>
            <span>Webkernel Core · {{ $currentChannel }}</span>
        </div>
        <h1 class="webkernel-upgrade-page-hero-title">
            Upgrade center for<br>
            <span class="webkernel-upgrade-page-hero-title-accent">sovereign infrastructure</span>
        </h1>
        <p class="webkernel-upgrade-page-hero-sub">
            Every update is cryptographically signed, atomically applied and fully reversible.
            Zero data loss. Full control.
        </p>
        <div class="webkernel-upgrade-page-hero-cta">
            @if(!$isUpToDate && $latestVersion !== '')
                <x-filament::button
                    color="primary"
                    icon="heroicon-m-rocket-launch"
                    size="lg"
                    wire:click="mountAction('update')"
                >
                    Upgrade to v{{ $latestVersion }}
                </x-filament::button>
            @elseif($isUpToDate)
                <x-filament::badge color="success" icon="heroicon-m-sparkles" size="lg">
                    This Instance is Up to Date
                </x-filament::badge>

                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-path"
                    size="xs"
                    outlined
                    wire:click="mountAction('check')"
                >
                    Check for Upgrades
                </x-filament::button>
            @else
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-path"
                    size="lg"
                    outlined
                    wire:click="mountAction('check')"
                >
                    Check for Upgrades
                </x-filament::button>
            @endif
        </div>
        <div class="webkernel-upgrade-page-hero-badges">
            @if(!$isUpToDate && $latestVersion !== '')
                <x-filament::badge color="warning" icon="heroicon-m-arrow-up-circle">
                    v{{ $latestVersion }} available
                </x-filament::badge>
            @elseif($isUpToDate)
                <x-filament::badge color="success" icon="heroicon-m-check-circle">
                    Up to date
                </x-filament::badge>
            @endif
            <x-filament::badge color="gray">{{ $currentChannel }}</x-filament::badge>
            @if($lastChecked !== '')
            <span
                class="webkernel-upgrade-page-hero-checked"
                x-data="{
                    ts: '{{ $lastChecked }}',
                    label: '',
                    tick() {
                        const diff = Math.floor((Date.now() - new Date(this.ts).getTime()) / 1000);
                        if      (diff < 60)    this.label = diff + 's ago';
                        else if (diff < 3600)  this.label = Math.floor(diff / 60) + 'm ago';
                        else if (diff < 86400) this.label = Math.floor(diff / 3600) + 'h ago';
                        else                   this.label = Math.floor(diff / 86400) + 'd ago';
                    }
                }"
                x-init="tick(); setInterval(() => tick(), 1000)"
                x-text="'Checked ' + label"
            ></span>
            @endif
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
        <div class="webkernel-upgrade-page-stat-label">Upgrade Status</div>
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
        <div class="webkernel-upgrade-page-status-strip" style="border-color: var(--wk-danger-border); margin-top: 0.75rem;">
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
     FEATURES (from latest tag annotation stored in DB)
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-features-section">
    <div class="webkernel-upgrade-page-section-eyebrow">Core Capabilities</div>
    <h2 class="webkernel-upgrade-page-section-title">
        Everything you need to build<br>great products on the web.
    </h2>
    <p class="webkernel-upgrade-page-section-sub">
        A sovereign, performance-optimized foundation built on Laravel, Filament and Livewire.
        Yours to own, deploy and extend.
    </p>
    @if($hasTagData)
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
    @else
    <p class="webkernel-upgrade-page-section-sub" style="margin-top:2rem;opacity:0.5;">
        Feature details will appear after the first update check fetches release data from the registry.
    </p>
    @endif
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
     VIDEO WALKTHROUGH (from latest tag annotation stored in DB)
────────────────────────────────────────────────────────────────────── --}}
@if($videoId !== '')
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
@endif

{{-- ────────────────────────────────────────────────────────────────────
     TECH STACK + PHILOSOPHY
────────────────────────────────────────────────────────────────────── --}}
<div class="webkernel-upgrade-page-two-col-section">
    <div class="webkernel-upgrade-page-two-col">
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

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
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
                            Yassine El Moumen · Founder, Numerimondes · Casablanca
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
                    <p class="webkernel-upgrade-page-nm-sub">Registered company · Casablanca, Morocco</p>
                </div>
                <x-filament::badge color="gray">EPL 2.0</x-filament::badge>
            </div>
        </div>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────
     DOCUMENTATION LINKS (from latest tag annotation stored in DB)
────────────────────────────────────────────────────────────────────── --}}
@if(count($docLinks) > 0)
<div class="webkernel-upgrade-page-docs-section">
    <div class="webkernel-upgrade-page-section-eyebrow">Resources</div>
    <h2 class="webkernel-upgrade-page-section-title">Documentation & Resources</h2>
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
@endif

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

{{-- ══════════════════════════════════════════════════════════════════════
     ROLLBACK MODAL (unchanged logic)
══════════════════════════════════════════════════════════════════════ --}}
<template x-if="rollbackOpen">
    <div class="webkernel-upgrade-page-backdrop" x-on:click.self="closeRollback()">
        <div class="webkernel-upgrade-page-panel" x-on:click.stop="">
            <div class="webkernel-upgrade-page-panel-head">
                <div>
                    <p class="webkernel-upgrade-page-panel-title">Rollback kernel</p>
                    <p class="webkernel-upgrade-page-panel-subtitle">Select a previous version</p>
                </div>
                <button class="webkernel-upgrade-page-panel-close" x-on:click="closeRollback()">
                    <x-filament::icon icon="heroicon-m-x-mark" />
                </button>
            </div>
            <div class="webkernel-upgrade-page-panel-body">
                <div class="webkernel-upgrade-page-rollback-warning">
                    ⚠️ Rolling back replaces core files. Backups are kept. Confirm the desired version.
                </div>
                @if(count($releases) === 0)
                    <p style="color:var(--wk-text-muted);font-size:0.875rem;text-align:center;padding:2rem 0;">
                        No releases found. Run a check for updates first.
                    </p>
                @endif
                @foreach($releases as $release)
                    <div
                        class="webkernel-upgrade-page-release {{ $release['current'] ? 'is-current' : '' }}"
                        x-on:click="selectRelease('{{ $release['version'] }}')"
                    >
                        <div class="webkernel-upgrade-page-release-meta">
                            <div class="webkernel-upgrade-page-release-head">
                                <span class="webkernel-upgrade-page-release-ver">v{{ $release['version'] }} — {{ $release['codename'] }}</span>
                                @if($release['current'])
                                    <x-filament::badge size="sm" color="primary">Current</x-filament::badge>
                                @endif
                            </div>
                            <p class="webkernel-upgrade-page-release-date">{{ $release['date'] }}</p>
                            <p class="webkernel-upgrade-page-release-notes">{{ $release['notes'] }}</p>
                        </div>
                        <div
                            class="webkernel-upgrade-page-release-radio"
                            :style="selectedRelease === '{{ $release['version'] }}' ? 'border-color: var(--wk-accent); background: var(--wk-accent); box-shadow: inset 0 0 0 4px var(--wk-surface-card);' : ''"
                        ></div>
                    </div>
                @endforeach
            </div>
            <div class="webkernel-upgrade-page-panel-footer">
                <x-filament::button color="gray" outlined x-on:click="closeRollback()">Cancel</x-filament::button>
                <x-filament::button
                    color="primary"
                    x-bind:disabled="!selectedRelease || selectedRelease === currentVersion"
                    wire:click="rollbackToVersion(selectedRelease)"
                >
                    Rollback
                </x-filament::button>
            </div>
        </div>
    </div>
</template>
</div>
</x-filament-panels::page>
