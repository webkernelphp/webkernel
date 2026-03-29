{{--
    Webkernel™ — Waterfall Release Page
    resources/views/release/waterfall.blade.php

    Data is injected from a controller or passed directly.
    All release data reads from WEBKERNEL_* constants defined in fast-boot.php.
--}}

@php
$release = [
    'version'      => defined('WEBKERNEL_VERSION')     ? WEBKERNEL_VERSION     : '1.3.32',
    'build'        => defined('WEBKERNEL_BUILD')        ? WEBKERNEL_BUILD       : 53,
    'semver'       => defined('WEBKERNEL_SEMVER')       ? WEBKERNEL_SEMVER      : '1.3.32+53',
    'codename'     => defined('WEBKERNEL_CODENAME')     ? WEBKERNEL_CODENAME    : 'Waterfall',
    'channel'      => defined('WEBKERNEL_CHANNEL')      ? WEBKERNEL_CHANNEL     : 'stable',
    'released_at'  => defined('WEBKERNEL_RELEASED_AT')  ? WEBKERNEL_RELEASED_AT : '2026-03-21',
    'commit'       => defined('WEBKERNEL_COMMIT')       ? WEBKERNEL_COMMIT      : 'unknown',
    'branch'       => defined('WEBKERNEL_BRANCH')       ? WEBKERNEL_BRANCH      : 'main',
    'requires'     => defined('WEBKERNEL_REQUIRES')     ? WEBKERNEL_REQUIRES    : [],
    'compatible'   => defined('WEBKERNEL_COMPATIBLE_WITH') ? WEBKERNEL_COMPATIBLE_WITH : [],
];

$aptitudes = [
    [
        'tag'   => 'System',
        'name'  => 'WebernelAPI',
        'desc'  => 'A unified API layer exposing real-time system state — CPU, memory, disk, PHP runtime, and instance identity — through a single, type-safe interface.',
        'badge' => 'Core',
    ],
    [
        'tag'   => 'Security',
        'name'  => 'Seal Enforcer',
        'desc'  => 'Runtime integrity verification that seals core classes at boot. Any tampered file triggers an immediate exception before a single request is served.',
        'badge' => 'Core',
    ],
    [
        'tag'   => 'Modules',
        'name'  => 'Module Orchestrator',
        'desc'  => 'Declarative module loading with topological dependency resolution, SHA-256 fingerprint caching, and zero manual configuration.',
        'badge' => 'Core',
    ],
    [
        'tag'   => 'Panel',
        'name'  => 'System Panel',
        'desc'  => 'A production-grade Filament 5 administration panel delivering live system telemetry, module management, and RBAC-controlled operations.',
        'badge' => 'Included',
    ],
    [
        'tag'   => 'Access',
        'name'  => 'RBAC Engine',
        'desc'  => 'Role-based access control built on webkernel()->auth()->can() — every gate enforced server-side, with UI hints as a visual layer only.',
        'badge' => 'Core',
    ],
    [
        'tag'   => 'UI',
        'name'  => 'Icon & Theme Registry',
        'desc'  => '4 600+ SVG icons from Lucide, Heroicons, and Simple Icons — centralized, cacheable, and injectable into any module via a single Blade component.',
        'badge' => 'Included',
    ],
];

$modules = [
    [
        'name'  => 'Invoicing',
        'desc'  => 'Issue invoices, track payments, export PDF — with a mobile PWA your team can use from the field.',
        'price' => '3 500 MAD',
        'tag'   => 'Finance',
    ],
    [
        'name'  => 'Calendars & ICS',
        'desc'  => 'Sync your schedules natively with Google Calendar, Apple Calendar, or any ICS client. No account required on their end.',
        'price' => '1 900 MAD',
        'tag'   => 'Scheduling',
    ],
    [
        'name'  => 'Kanban Boards',
        'desc'  => 'Drag-and-drop project management, private to your organization — no external service, no data leaving your server.',
        'price' => '2 500 MAD',
        'tag'   => 'Productivity',
    ],
    [
        'name'  => 'Website Builder',
        'desc'  => 'Design and publish your company website from the same platform. You own the site, the content, and the hosting.',
        'price' => '4 900 MAD',
        'tag'   => 'Presence',
    ],
];

$problems = [
    'Monthly fees that compound every year, forever',
    'Your data on someone else\'s servers, under someone else\'s terms',
    'Features removed or paywalled after you\'ve built around them',
    'A vendor who can raise prices because you\'re locked in',
    'No software access if you miss one payment',
    'Compliance failures when data crosses borders involuntarily',
];

$solutions = [
    'One-time purchase per module — yours permanently',
    'Deploy on your server, your country, your infrastructure',
    'Open-source core — inspect every line, forever',
    'Annual updates capped at 25% of original price, and optional',
    'Works fully offline and air-gapped for regulated industries',
    'Complete data residency control — GDPR, healthcare, government ready',
];

$sectors = [
    ['icon' => '🏢', 'label' => 'Small & medium enterprises'],
    ['icon' => '🏫', 'label' => 'Schools & universities'],
    ['icon' => '🏥', 'label' => 'Healthcare & clinics'],
    ['icon' => '🏦', 'label' => 'Finance & accounting'],
    ['icon' => '🏛️', 'label' => 'Government & public bodies'],
    ['icon' => '⚖️', 'label' => 'Legal & consulting'],
    ['icon' => '🚚', 'label' => 'Logistics & manufacturing'],
    ['icon' => '🌍', 'label' => 'NGOs & humanitarian aid'],
];

$pricing = [
    [
        'tier'     => 'Foundation',
        'amount'   => 'Free',
        'period'   => 'Open-source core · forever',
        'featured' => false,
        'features' => [
            'Webkernel core — EPL-2.0',
            'System administration panel',
            'Module orchestration engine',
            'Self-hosted on your infrastructure',
            'Community support',
        ],
        'cta'      => 'Get started free',
        'cta_href' => 'https://webkernelphp.com',
    ],
    [
        'tier'     => 'Business',
        'amount'   => 'From 3 500 MAD',
        'period'   => 'Per module · one-time purchase',
        'featured' => true,
        'features' => [
            'All Foundation features',
            'Invoicing, Calendars, Kanban, or Website module',
            'Perpetual license — never expires',
            'Optional updates at ≤ 25% of purchase price / year',
            'Priority support available',
            'Founding client: −10 000 MAD (limited · expires May 2026)',
        ],
        'cta'      => 'Request a demo',
        'cta_href' => '#contact',
    ],
    [
        'tier'     => 'Sovereign',
        'amount'   => 'Custom',
        'period'   => 'Governments & critical infrastructure',
        'featured' => false,
        'features' => [
            'All Business features',
            'Fully air-gapped deployment',
            'Local integrity verification',
            'Zero outbound connection required',
            'Dedicated support contract',
        ],
        'cta'      => 'Contact Numerimondes',
        'cta_href' => '#contact',
    ],
];
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Webkernel™ {{ ucfirst($release['codename']) }} — {{ $release['semver'] }}</title>
<meta name="description" content="Webkernel {{ ucfirst($release['codename']) }} release — sovereign application infrastructure for organizations that demand full control.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">

<style>
/* ─── Reset & Base ──────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 16px; }

/* ─── Design Tokens ─────────────────────────────────────────────────────────── */
:root {
  /* Palette */
  --void:      #08090a;
  --deep:      #0d0e10;
  --surface:   #121316;
  --elevated:  #17191d;
  --border:    rgba(255,255,255,0.07);
  --border-md: rgba(255,255,255,0.12);
  --gold:      #c4954a;
  --gold-soft: #e2bf87;
  --gold-dim:  rgba(196,149,74,0.15);
  --ink:       #f0ede8;
  --ink-2:     rgba(240,237,232,0.65);
  --ink-3:     rgba(240,237,232,0.35);
  --danger:    #8b3a3a;
  --success:   #2d6a4a;

  /* Typography */
  --serif:   'Cormorant Garamond', Georgia, serif;
  --sans:    'DM Sans', sans-serif;
  --mono:    'DM Mono', 'Fira Mono', monospace;

  /* Spacing */
  --section: 7rem;
  --max-w:   1120px;
}

/* ─── Global ────────────────────────────────────────────────────────────────── */
body {
  background: var(--void);
  color: var(--ink);
  font-family: var(--sans);
  font-size: 1rem;
  line-height: 1.65;
  -webkit-font-smoothing: antialiased;
  overflow-x: hidden;
}

/* Grain overlay */
body::after {
  content: '';
  position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 9999; opacity: 0.4;
}

.container { max-width: var(--max-w); margin: 0 auto; padding: 0 2rem; }
section { border-bottom: 1px solid var(--border); }

/* ─── Navigation ────────────────────────────────────────────────────────────── */
.nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  height: 60px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 2rem;
  background: rgba(8,9,10,0.85);
  backdrop-filter: blur(20px) saturate(1.5);
  border-bottom: 1px solid var(--border);
}
.nav-brand {
  display: flex; align-items: center; gap: 0.75rem;
  text-decoration: none;
}
.nav-wordmark {
  font-family: var(--serif); font-size: 1.05rem; font-weight: 600;
  color: var(--ink); letter-spacing: 0.03em;
}
.nav-wordmark span { color: var(--gold); }
.nav-release-badge {
  font-family: var(--mono); font-size: 0.65rem;
  color: var(--gold); background: var(--gold-dim);
  padding: 0.2rem 0.6rem;
  border: 1px solid rgba(196,149,74,0.25);
  letter-spacing: 0.08em; text-transform: uppercase;
}
.nav-links {
  display: flex; align-items: center; gap: 0.25rem; list-style: none;
}
.nav-links a {
  padding: 0.4rem 0.875rem;
  color: var(--ink-3); text-decoration: none;
  font-size: 0.85rem; font-weight: 400;
  border-radius: 6px;
  transition: color 0.15s, background 0.15s;
}
.nav-links a:hover { color: var(--ink); background: rgba(255,255,255,0.05); }
.nav-cta-btn {
  padding: 0.45rem 1.1rem;
  background: var(--gold); color: var(--void);
  font-size: 0.82rem; font-weight: 600;
  font-family: var(--sans); letter-spacing: 0.04em;
  text-decoration: none; border-radius: 6px;
  transition: background 0.15s, transform 0.1s;
  white-space: nowrap;
}
.nav-cta-btn:hover { background: var(--gold-soft); transform: translateY(-1px); }

/* ─── Event Banner ───────────────────────────────────────────────────────────── */
.event-banner {
  margin-top: 60px;
  background: linear-gradient(135deg, var(--deep) 0%, var(--surface) 100%);
  border-bottom: 1px solid var(--border);
  padding: 0.6rem 2rem;
  display: flex; align-items: center; justify-content: center; gap: 1.5rem;
  flex-wrap: wrap;
}
.event-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--gold);
  box-shadow: 0 0 8px var(--gold);
  animation: pulse 2.5s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 8px var(--gold); }
  50%       { opacity: 0.5; box-shadow: 0 0 3px var(--gold); }
}
.event-text {
  font-family: var(--mono); font-size: 0.72rem;
  color: var(--ink-2); letter-spacing: 0.08em; text-transform: uppercase;
}
.event-text strong { color: var(--gold-soft); font-weight: 400; }
.event-sep { color: var(--ink-3); }

/* ─── Hero ───────────────────────────────────────────────────────────────────── */
.hero {
  padding: calc(var(--section) * 1.2) 0 var(--section);
  position: relative; overflow: hidden;
}
.hero-bg-glow {
  position: absolute; top: -200px; left: 50%;
  transform: translateX(-50%);
  width: 800px; height: 800px;
  background: radial-gradient(circle, rgba(196,149,74,0.055) 0%, transparent 65%);
  pointer-events: none;
}
.hero-inner { position: relative; }
.hero-kicker {
  display: inline-flex; align-items: center; gap: 0.75rem;
  font-family: var(--mono); font-size: 0.72rem; letter-spacing: 0.12em;
  color: var(--gold); text-transform: uppercase;
  margin-bottom: 2.5rem;
}
.hero-kicker::before {
  content: '';
  width: 28px; height: 1px; background: var(--gold); opacity: 0.5;
}
.hero-title {
  font-family: var(--serif);
  font-size: clamp(4.5rem, 9vw, 9rem);
  font-weight: 300; line-height: 0.93;
  letter-spacing: -0.02em;
  margin-bottom: 2.5rem;
}
.hero-title .codename {
  display: block;
  font-style: italic; color: var(--gold-soft);
}
.hero-title .tagline {
  display: block;
  font-style: normal; color: var(--ink);
}
.hero-desc {
  max-width: 560px;
  color: var(--ink-2); font-size: 1.1rem; line-height: 1.75;
  margin-bottom: 3rem;
}
.hero-actions {
  display: flex; gap: 0.875rem; flex-wrap: wrap; align-items: center;
  margin-bottom: 4rem;
}

/* Filament-style primary button */
.btn-fi-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.7rem 1.75rem;
  background: var(--gold); color: var(--void);
  font-family: var(--sans); font-size: 0.875rem; font-weight: 600;
  text-decoration: none; border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.4), 0 0 0 1px rgba(196,149,74,0.3);
  transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
}
.btn-fi-primary:hover {
  background: var(--gold-soft); transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(196,149,74,0.25), 0 0 0 1px rgba(196,149,74,0.4);
}

/* Filament-style outlined button */
.btn-fi-outline {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.7rem 1.5rem;
  background: transparent; color: var(--ink-2);
  font-family: var(--sans); font-size: 0.875rem; font-weight: 400;
  text-decoration: none; border-radius: 8px;
  border: 1px solid var(--border-md);
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.btn-fi-outline:hover {
  border-color: rgba(255,255,255,0.25); color: var(--ink);
  background: rgba(255,255,255,0.04);
}

.hero-meta-row {
  display: flex; gap: 2rem; flex-wrap: wrap;
  padding-top: 2rem;
  border-top: 1px solid var(--border);
}
.hero-meta-item {
  font-family: var(--mono); font-size: 0.7rem; color: var(--ink-3);
  letter-spacing: 0.06em;
}
.hero-meta-item strong { color: var(--ink-2); font-weight: 400; }

/* ─── Release Intro Block ────────────────────────────────────────────────────── */
.release-intro {
  padding: var(--section) 0;
  background: linear-gradient(180deg, var(--void) 0%, var(--deep) 100%);
}
.release-intro-inner {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 5rem; align-items: center;
}
.release-intro-text h2 {
  font-family: var(--serif);
  font-size: clamp(2.2rem, 4vw, 3.75rem);
  font-weight: 300; line-height: 1.15; margin-bottom: 1.5rem;
}
.release-intro-text h2 em { font-style: italic; color: var(--gold-soft); }
.release-intro-text p {
  color: var(--ink-2); font-size: 1.05rem; line-height: 1.8;
  margin-bottom: 1.25rem;
}
.release-meta-box {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}
.rmb-header {
  padding: 0.75rem 1.25rem;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 0.5rem;
}
.rmb-dots { display: flex; gap: 5px; }
.rmb-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--border-md);
}
.rmb-title {
  font-family: var(--mono); font-size: 0.68rem;
  color: var(--ink-3); letter-spacing: 0.08em; margin-left: auto;
  text-align: right;
}
.rmb-body { padding: 1.5rem 1.25rem; }
.rmb-row {
  display: flex; justify-content: space-between; align-items: baseline;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--border);
  font-family: var(--mono); font-size: 0.78rem;
}
.rmb-row:last-child { border-bottom: none; }
.rmb-key { color: var(--ink-3); }
.rmb-val { color: var(--ink); }
.rmb-val.gold { color: var(--gold-soft); }
.rmb-val.badge {
  background: rgba(45,106,74,0.2); color: #6fcf9a;
  border: 1px solid rgba(45,106,74,0.35);
  padding: 0.1rem 0.5rem; border-radius: 4px; font-size: 0.68rem;
}

/* ─── Aptitudes Grid ─────────────────────────────────────────────────────────── */
.aptitudes { padding: var(--section) 0; }
.section-eyebrow {
  font-family: var(--mono); font-size: 0.72rem; letter-spacing: 0.12em;
  color: var(--gold); text-transform: uppercase;
  margin-bottom: 1rem;
}
.section-title {
  font-family: var(--serif);
  font-size: clamp(2rem, 3.5vw, 3.25rem);
  font-weight: 300; line-height: 1.15; margin-bottom: 0.75rem;
}
.section-title em { font-style: italic; color: var(--gold-soft); }
.section-sub {
  color: var(--ink-2); font-size: 1.025rem; line-height: 1.7;
  max-width: 580px; margin-bottom: 3.5rem;
}
.apt-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 1px; background: var(--border);
  border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
}
.apt-card {
  background: var(--surface);
  padding: 2rem;
  transition: background 0.2s;
}
.apt-card:hover { background: var(--elevated); }
.apt-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem;
}
.apt-tag {
  font-family: var(--mono); font-size: 0.65rem; letter-spacing: 0.1em;
  color: var(--gold); text-transform: uppercase;
}
.fi-badge {
  font-family: var(--sans); font-size: 0.68rem; font-weight: 500;
  padding: 0.15rem 0.6rem; border-radius: 20px;
  background: rgba(196,149,74,0.12);
  color: var(--gold-soft);
  border: 1px solid rgba(196,149,74,0.2);
}
.fi-badge.included {
  background: rgba(45,106,74,0.15); color: #6fcf9a;
  border-color: rgba(45,106,74,0.25);
}
.apt-card h3 {
  font-family: var(--serif); font-size: 1.35rem; font-weight: 400;
  margin-bottom: 0.625rem; line-height: 1.2;
}
.apt-card p { color: var(--ink-3); font-size: 0.875rem; line-height: 1.7; }

/* ─── Problems & Solutions ───────────────────────────────────────────────────── */
.compare { padding: var(--section) 0; }
.compare-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 4rem; align-items: start;
}
.compare-col-label {
  font-family: var(--mono); font-size: 0.68rem; letter-spacing: 0.12em;
  text-transform: uppercase; margin-bottom: 1.5rem;
  display: flex; align-items: center; gap: 0.75rem;
}
.compare-col-label.bad  { color: #a05050; }
.compare-col-label.good { color: var(--gold); }
.compare-list { list-style: none; }
.compare-list li {
  display: flex; gap: 1rem; align-items: baseline;
  padding: 0.875rem 0;
  border-bottom: 1px solid var(--border);
  font-size: 0.95rem; line-height: 1.55;
}
.compare-list li:last-child { border-bottom: none; }
.compare-list.bad  li { color: var(--ink-3); }
.compare-list.good li { color: var(--ink-2); }
.compare-icon-bad  { color: #8b3a3a; font-size: 0.9rem; flex-shrink: 0; }
.compare-icon-good { color: var(--gold); font-size: 0.8rem; flex-shrink: 0; }

/* ─── Modules ────────────────────────────────────────────────────────────────── */
.modules { padding: var(--section) 0; }
.modules-header {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 3rem; flex-wrap: wrap; gap: 1.5rem;
}
.modules-header p { color: var(--ink-3); font-size: 0.875rem; max-width: 340px; text-align: right; line-height: 1.65; }
.mod-grid {
  display: grid; grid-template-columns: repeat(2, 1fr);
  gap: 1px; background: var(--border);
  border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
}
.mod-card {
  background: var(--surface); padding: 2rem 2.25rem;
  transition: background 0.15s;
}
.mod-card:hover { background: var(--elevated); }
.mod-tag {
  font-family: var(--mono); font-size: 0.65rem; letter-spacing: 0.1em;
  color: var(--gold); text-transform: uppercase; margin-bottom: 0.75rem;
}
.mod-card h3 {
  font-family: var(--serif); font-size: 1.5rem; font-weight: 400;
  margin-bottom: 0.625rem;
}
.mod-card p { color: var(--ink-3); font-size: 0.9rem; line-height: 1.7; margin-bottom: 1.5rem; }
.mod-price-row {
  display: flex; align-items: center; justify-content: space-between;
  padding-top: 1.25rem; border-top: 1px solid var(--border);
}
.mod-price {
  font-family: var(--mono); font-size: 0.78rem;
}
.mod-price strong { color: var(--gold-soft); font-size: 1rem; }
.mod-price span { color: var(--ink-3); font-size: 0.72rem; }
.mod-external-note {
  font-size: 0.72rem; color: var(--ink-3);
  font-style: italic; font-family: var(--serif);
}

/* ─── Founder Quote ──────────────────────────────────────────────────────────── */
.quote-block {
  padding: var(--section) 0;
  text-align: center;
  background: linear-gradient(180deg, var(--void) 0%, var(--deep) 50%, var(--void) 100%);
}
.quote-block blockquote {
  font-family: var(--serif); font-style: italic;
  font-size: clamp(1.5rem, 3vw, 2.75rem);
  font-weight: 300; line-height: 1.4;
  max-width: 820px; margin: 0 auto 2rem;
  color: rgba(240,237,232,0.8);
}
.quote-block blockquote::before { content: '\201C'; color: var(--gold); margin-right: 0.15em; }
.quote-block blockquote::after  { content: '\201D'; color: var(--gold); margin-left: 0.15em; }
.quote-attr {
  font-family: var(--mono); font-size: 0.7rem; color: var(--ink-3);
  letter-spacing: 0.1em; text-transform: uppercase;
}
.quote-attr a { color: var(--gold); text-decoration: none; }

/* ─── Sectors ────────────────────────────────────────────────────────────────── */
.sectors-section { padding: var(--section) 0; }
.sectors-grid {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 1px; background: var(--border);
  border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
  margin-top: 3rem;
}
.sector-card {
  background: var(--surface); padding: 1.75rem 1.5rem;
  transition: background 0.15s;
}
.sector-card:hover { background: var(--elevated); }
.sector-icon { font-size: 1.35rem; display: block; margin-bottom: 0.625rem; }
.sector-label { font-size: 0.875rem; color: var(--ink-2); line-height: 1.4; }

/* ─── Pricing ────────────────────────────────────────────────────────────────── */
.pricing-section { padding: var(--section) 0; }
.pricing-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 1px; background: var(--border);
  border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
  margin-top: 3.5rem;
}
.pricing-card {
  background: var(--surface); padding: 2.5rem 2rem;
  display: flex; flex-direction: column;
}
.pricing-card.featured {
  background: var(--elevated);
  outline: 1px solid rgba(196,149,74,0.35);
  outline-offset: -1px;
}
.pricing-tier {
  font-family: var(--mono); font-size: 0.68rem; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--gold); margin-bottom: 1.5rem;
}
.pricing-amount {
  font-family: var(--serif); font-size: 2.25rem; font-weight: 300;
  line-height: 1; margin-bottom: 0.375rem;
}
.pricing-period { font-size: 0.8rem; color: var(--ink-3); margin-bottom: 2rem; }
.pricing-features { list-style: none; flex: 1; margin-bottom: 2rem; }
.pricing-features li {
  display: flex; gap: 0.75rem; align-items: baseline;
  padding: 0.6rem 0;
  border-bottom: 1px solid var(--border);
  font-size: 0.875rem; color: var(--ink-2);
}
.pricing-features li:last-child { border-bottom: none; }
.pricing-features li::before { content: '—'; color: var(--gold); font-size: 0.7rem; flex-shrink: 0; }

/* Filament-style buttons in pricing */
.fi-btn-primary {
  display: block; text-align: center;
  padding: 0.7rem 1.25rem;
  background: var(--gold); color: var(--void);
  font-family: var(--sans); font-size: 0.85rem; font-weight: 600;
  text-decoration: none; border-radius: 8px;
  transition: background 0.15s;
}
.fi-btn-primary:hover { background: var(--gold-soft); }
.fi-btn-ghost {
  display: block; text-align: center;
  padding: 0.7rem 1.25rem;
  background: transparent; color: var(--ink-2);
  font-family: var(--sans); font-size: 0.85rem;
  text-decoration: none; border-radius: 8px;
  border: 1px solid var(--border-md);
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.fi-btn-ghost:hover {
  border-color: rgba(255,255,255,0.22); color: var(--ink);
  background: rgba(255,255,255,0.04);
}

/* ─── Final CTA ──────────────────────────────────────────────────────────────── */
.final-cta {
  padding: calc(var(--section) * 1.4) 0;
  text-align: center; position: relative; overflow: hidden;
}
.final-cta-glow {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 700px; height: 500px;
  background: radial-gradient(ellipse, rgba(196,149,74,0.07) 0%, transparent 70%);
  pointer-events: none;
}
.final-cta h2 {
  font-family: var(--serif);
  font-size: clamp(2.5rem, 5vw, 5rem);
  font-weight: 300; line-height: 1.1; margin-bottom: 1.5rem;
}
.final-cta h2 em { font-style: italic; color: var(--gold-soft); }
.final-cta p {
  color: var(--ink-2); font-size: 1.1rem; line-height: 1.75;
  max-width: 500px; margin: 0 auto 3rem;
}
.final-cta-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem; }
.founder-sig {
  font-family: var(--mono); font-size: 0.7rem; color: var(--ink-3);
  letter-spacing: 0.06em;
}
.founder-sig a { color: var(--gold); text-decoration: none; }
.founder-sig a:hover { color: var(--gold-soft); }

/* ─── Footer ─────────────────────────────────────────────────────────────────── */
.footer {
  padding: 2.5rem 2rem;
  border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 1.25rem;
}
.footer-brand {
  font-family: var(--serif); font-size: 0.95rem; font-weight: 600; color: var(--ink-2);
}
.footer-brand span { color: var(--gold); }
.footer-links { display: flex; gap: 1.75rem; flex-wrap: wrap; }
.footer-links a {
  font-size: 0.8rem; color: var(--ink-3); text-decoration: none;
  transition: color 0.15s;
}
.footer-links a:hover { color: var(--ink); }
.footer-copy { font-size: 0.75rem; color: var(--ink-3); }

/* ─── Divider ────────────────────────────────────────────────────────────────── */
.section-divider {
  width: 100%; height: 1px; background: var(--border);
  margin: 0;
}

/* ─── Animations ─────────────────────────────────────────────────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
.hero-kicker  { animation: fadeUp 0.5s ease 0.1s both; }
.hero-title   { animation: fadeUp 0.6s ease 0.25s both; }
.hero-desc    { animation: fadeUp 0.6s ease 0.4s both; }
.hero-actions { animation: fadeUp 0.6s ease 0.5s both; }
.hero-meta-row{ animation: fadeUp 0.6s ease 0.6s both; }

/* ─── Responsive ─────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .apt-grid { grid-template-columns: 1fr 1fr; }
  .sectors-grid { grid-template-columns: repeat(2, 1fr); }
  .pricing-grid { grid-template-columns: 1fr; }
  .release-intro-inner { grid-template-columns: 1fr; gap: 2.5rem; }
  .compare-grid { grid-template-columns: 1fr; gap: 2.5rem; }
}
@media (max-width: 640px) {
  :root { --section: 4.5rem; }
  .apt-grid { grid-template-columns: 1fr; }
  .mod-grid { grid-template-columns: 1fr; }
  .nav-links { display: none; }
  .hero-title { font-size: 3.5rem; }
  .modules-header { flex-direction: column; }
  .modules-header p { text-align: left; }
}
</style>
</head>
<body>

{{-- ── Navigation ── --}}
<nav class="nav">
  <a href="https://webkernelphp.com" class="nav-brand">
    <span class="nav-wordmark">Webkernel<span>™</span></span>
    <span class="nav-release-badge">{{ ucfirst($release['codename']) }} · {{ $release['semver'] }}</span>
  </a>
  <ul class="nav-links">
    <li><a href="#aptitudes">Aptitudes</a></li>
    <li><a href="#modules">Modules</a></li>
    <li><a href="#sectors">For whom</a></li>
    <li><a href="#pricing">Pricing</a></li>
  </ul>
  <a href="#contact" class="nav-cta-btn">Request a demo</a>
</nav>

{{-- ── Event Banner ── --}}
<div class="event-banner">
  <span class="event-dot"></span>
  <span class="event-text">
    <strong>{{ ucfirst($release['codename']) }}</strong> is now available
  </span>
  <span class="event-sep">·</span>
  <span class="event-text">Version <strong>{{ $release['semver'] }}</strong></span>
  <span class="event-sep">·</span>
  <span class="event-text">Released <strong>{{ \Carbon\Carbon::parse($release['released_at'])->format('F j, Y') }}</strong></span>
  <span class="event-sep">·</span>
  <span class="event-text">Channel: <strong>{{ ucfirst($release['channel']) }}</strong></span>
</div>

{{-- ── Hero ── --}}
<section class="hero">
  <div class="hero-bg-glow"></div>
  <div class="container hero-inner">

    <div class="hero-kicker">Webkernel™ Foundation Release</div>

    <h1 class="hero-title">
      <span class="codename">{{ ucfirst($release['codename']) }}</span>
      <span class="tagline">Your software.<br>Your rules. Forever.</span>
    </h1>

    <p class="hero-desc">
      A sovereign application platform that gives your business complete ownership
      over its software infrastructure — with no subscriptions that hold your data
      hostage, and no vendor who can change the terms overnight.
    </p>

    <div class="hero-actions">
      <a href="#contact" class="btn-fi-primary">Request a demo →</a>
      <a href="#aptitudes" class="btn-fi-outline">Explore the release</a>
      <a href="https://github.com/webkernelphp/foundation" class="btn-fi-outline" target="_blank">GitHub</a>
    </div>

    <div class="hero-meta-row">
      @if(!empty($release['requires']))
        @foreach($release['requires'] as $dep => $ver)
          <div class="hero-meta-item"><strong>{{ ucfirst($dep) }}</strong> {{ $ver }}</div>
        @endforeach
      @endif
      <div class="hero-meta-item"><strong>License</strong> EPL-2.0 Open Core</div>
      @if($release['commit'] !== 'unknown')
        <div class="hero-meta-item"><strong>Commit</strong> {{ $release['commit'] }}</div>
      @endif
    </div>

  </div>
</section>

{{-- ── Release Intro ── --}}
<section class="release-intro">
  <div class="container">
    <div class="release-intro-inner">
      <div class="release-intro-text">
        <h2>The core is free.<br><em>You pay for what you use.</em></h2>
        <p>
          Unlike SaaS platforms that charge you monthly for software you will never own,
          Webkernel's foundation is open-source at no cost.
        </p>
        <p>
          You purchase modules once, permanently. Your license does not expire
          when you stop paying a subscription — because there is no subscription.
        </p>
      </div>
      <div class="release-meta-box">
        <div class="rmb-header">
          <div class="rmb-dots">
            <span class="rmb-dot"></span>
            <span class="rmb-dot"></span>
            <span class="rmb-dot"></span>
          </div>
          <div class="rmb-title">fast-boot.php · release constants</div>
        </div>
        <div class="rmb-body">
          <div class="rmb-row">
            <span class="rmb-key">WEBKERNEL_VERSION</span>
            <span class="rmb-val gold">'{{ $release['version'] }}'</span>
          </div>
          <div class="rmb-row">
            <span class="rmb-key">WEBKERNEL_SEMVER</span>
            <span class="rmb-val gold">'{{ $release['semver'] }}'</span>
          </div>
          <div class="rmb-row">
            <span class="rmb-key">WEBKERNEL_CODENAME</span>
            <span class="rmb-val">'{{ $release['codename'] }}'</span>
          </div>
          <div class="rmb-row">
            <span class="rmb-key">WEBKERNEL_CHANNEL</span>
            <span class="rmb-val badge">{{ $release['channel'] }}</span>
          </div>
          <div class="rmb-row">
            <span class="rmb-key">WEBKERNEL_RELEASED_AT</span>
            <span class="rmb-val">'{{ $release['released_at'] }}'</span>
          </div>
          @if($release['commit'] !== 'unknown')
          <div class="rmb-row">
            <span class="rmb-key">WEBKERNEL_COMMIT</span>
            <span class="rmb-val">'{{ $release['commit'] }}'</span>
          </div>
          @endif
          @foreach($release['requires'] as $dep => $ver)
          <div class="rmb-row">
            <span class="rmb-key">requires.{{ $dep }}</span>
            <span class="rmb-val">'{{ $ver }}'</span>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ── Aptitudes ── --}}
<section class="aptitudes" id="aptitudes">
  <div class="container">
    <div class="section-eyebrow">What ships in {{ ucfirst($release['codename']) }}</div>
    <h2 class="section-title">Six core <em>aptitudes.</em><br>One coherent platform.</h2>
    <p class="section-sub">
      Each aptitude is a focused capability built into the kernel — not a plugin,
      not a dependency. They compose into a complete sovereign infrastructure layer.
    </p>
    <div class="apt-grid">
      @foreach($aptitudes as $apt)
      <div class="apt-card">
        <div class="apt-header">
          <span class="apt-tag">{{ $apt['tag'] }}</span>
          <span class="fi-badge {{ strtolower($apt['badge']) === 'included' ? 'included' : '' }}">
            {{ $apt['badge'] }}
          </span>
        </div>
        <h3>{{ $apt['name'] }}</h3>
        <p>{{ $apt['desc'] }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Compare ── --}}
<section class="compare">
  <div class="container">
    <div class="section-eyebrow">Why organizations switch</div>
    <h2 class="section-title">What conventional<br>software <em>costs you.</em></h2>
    <div class="compare-grid" style="margin-top: 3rem;">
      <div>
        <div class="compare-col-label bad">Without Webkernel</div>
        <ul class="compare-list bad">
          @foreach($problems as $problem)
          <li>
            <span class="compare-icon-bad">✕</span>
            {{ $problem }}
          </li>
          @endforeach
        </ul>
      </div>
      <div>
        <div class="compare-col-label good">With Webkernel</div>
        <ul class="compare-list good">
          @foreach($solutions as $solution)
          <li>
            <span class="compare-icon-good">✓</span>
            {{ $solution }}
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</section>

{{-- ── Modules ── --}}
<section class="modules" id="modules">
  <div class="container">
    <div class="modules-header">
      <div>
        <div class="section-eyebrow">First-party modules</div>
        <h2 class="section-title">Business tools,<br><em>purchased once.</em></h2>
      </div>
      <p>
        Modules are distributed independently from the core. Each is a signed,
        versioned package you purchase once and own permanently.
        More modules available at webkernelphp.com.
      </p>
    </div>
    <div class="mod-grid">
      @foreach($modules as $module)
      <div class="mod-card">
        <div class="mod-tag">{{ $module['tag'] }}</div>
        <h3>{{ $module['name'] }}</h3>
        <p>{{ $module['desc'] }}</p>
        <div class="mod-price-row">
          <div class="mod-price">
            From <strong>{{ $module['price'] }}</strong>
            <span>· one-time</span>
          </div>
          <span class="mod-external-note">Distributed separately</span>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Quote ── --}}
<section class="quote-block">
  <div class="container">
    <blockquote>
      Software should be a reliable workforce under your direct command —
      not a monthly subscription that holds your operations hostage.
    </blockquote>
    <div class="quote-attr">
      Yassine El Moumen · Founder &amp; Architect ·
      <a href="https://www.numerimondes.com" target="_blank">Numerimondes</a> ·
      Casablanca, Morocco
    </div>
  </div>
</section>

{{-- ── Sectors ── --}}
<section class="sectors-section" id="sectors">
  <div class="container">
    <div class="section-eyebrow">Who it serves</div>
    <h2 class="section-title">Built for organizations<br>that cannot afford <em>dependencies.</em></h2>
    <div class="sectors-grid">
      @foreach($sectors as $sector)
      <div class="sector-card">
        <span class="sector-icon">{{ $sector['icon'] }}</span>
        <span class="sector-label">{{ $sector['label'] }}</span>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Pricing ── --}}
<section class="pricing-section" id="pricing">
  <div class="container">
    <div class="section-eyebrow">Licensing</div>
    <h2 class="section-title">Simple, <em>honest</em> pricing.</h2>
    <p class="section-sub">
      The core is always free. You pay once for the modules you need.
      No recurring lock-in, no hidden upgrade fees.
    </p>
    <div class="pricing-grid">
      @foreach($pricing as $plan)
      <div class="pricing-card {{ $plan['featured'] ? 'featured' : '' }}">
        <div class="pricing-tier">{{ $plan['tier'] }}</div>
        <div class="pricing-amount">{{ $plan['amount'] }}</div>
        <div class="pricing-period">{{ $plan['period'] }}</div>
        <ul class="pricing-features">
          @foreach($plan['features'] as $feature)
          <li>{{ $feature }}</li>
          @endforeach
        </ul>
        @if($plan['featured'])
          <a href="{{ $plan['cta_href'] }}" class="fi-btn-primary">{{ $plan['cta'] }}</a>
        @else
          <a href="{{ $plan['cta_href'] }}" class="fi-btn-ghost">{{ $plan['cta'] }}</a>
        @endif
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── Final CTA ── --}}
<section class="final-cta" id="contact">
  <div class="final-cta-glow"></div>
  <div class="container" style="position:relative;">
    <h2>Ready to own<br>your <em>software?</em></h2>
    <p>
      Book a 30-minute call. We will show you the platform live
      and answer every question you have.
    </p>
    <div class="final-cta-actions">
      <a href="https://www.linkedin.com/in/elmoumenyassine/" class="btn-fi-primary" target="_blank">
        Book a call on LinkedIn →
      </a>
      <a href="tel:00212620990692" class="btn-fi-outline">+212 6 2099 0692</a>
    </div>
    <div class="founder-sig">
      Yassine El Moumen ·
      <a href="https://webkernelphp.com" target="_blank">webkernelphp.com</a> ·
      <a href="https://www.numerimondes.com" target="_blank">numerimondes.com</a> ·
      Casablanca, Morocco
    </div>
  </div>
</section>

{{-- ── Footer ── --}}
<footer class="footer">
  <div class="footer-brand">Webkernel<span>™</span> · Numerimondes</div>
  <div class="footer-links">
    <a href="https://webkernelphp.com" target="_blank">Documentation</a>
    <a href="https://github.com/webkernelphp/foundation" target="_blank">GitHub</a>
    <a href="https://www.linkedin.com/in/elmoumenyassine/" target="_blank">LinkedIn</a>
    <a href="https://www.numerimondes.com" target="_blank">Numerimondes</a>
    <a href="#contact">Contact</a>
  </div>
  <div class="footer-copy">
    © {{ date('Y') }} Numerimondes · EPL-2.0 Open Core ·
    Webkernel™ {{ $release['semver'] }} · {{ ucfirst($release['codename']) }}
  </div>
</footer>

</body>
</html>
