{{--
    Webkernel™ — Waterfall Release Launch Page
    resources/views/release/waterfall.blade.php

    Requires in layout: @filamentStyles
<script>
window.addEventListener('load', function() {

// ── Scroll progress bar ──────────────────────────────────────
const prog = document.getElementById('wk-progress');
if (prog) {
    window.addEventListener('scroll', () => {
    const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100;
    prog.style.width = Math.min(pct, 100) + '%';
    }, {passive:true});
}

// ── Cursor glow — desktop only ───────────────────────────────
const cur = document.getElementById('wk-cursor');
if (cur && window.matchMedia('(pointer:fine)').matches) {
    cur.style.opacity = '1';
    document.addEventListener('mousemove', e => {
    cur.style.left = e.clientX + 'px';
    cur.style.top  = e.clientY + 'px';
    }, {passive:true});
}

// ── Ambient background orbs — continuous slow drift ──────────
(function() {
    const orbs = document.querySelectorAll('.wk-orb');
    orbs.forEach((orb, i) => {
        const seed = i * 137.508;
        let t = seed;
        function tick() {
            t += 0.003;
            const x = Math.sin(t * 0.7 + seed) * 40;
            const y = Math.cos(t * 0.5 + seed) * 30;
            orb.style.transform = `translate(${x}px, ${y}px)`;
            requestAnimationFrame(tick);
        }
        tick();
    });
})();

// ── Word-by-word reveal on hero headline ─────────────────────
(function() {
    const targets = document.querySelectorAll('.wk-word-reveal');
    targets.forEach(el => {
        const html = el.innerHTML;
        el.innerHTML = html.replace(/([\w'']+)/g, '<span class="wk-word" style="display:inline-block;opacity:0;transform:translateY(18px) skewX(-4deg);transition:opacity .45s ease,transform .45s ease;">$1</span>');
    });
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const words = e.target.querySelectorAll('.wk-word');
            words.forEach((w, i) => {
                setTimeout(() => {
                    w.style.opacity = '1';
                    w.style.transform = 'none';
                }, i * 55 + 80);
            });
            obs.unobserve(e.target);
        });
    }, { threshold: 0.2 });
    targets.forEach(el => obs.observe(el));
})();

// ── Draggable carousel ───────────────────────────────────────
const c = document.getElementById('wk-carousel');
if (c) {
    let isDown = false, startX, scrollLeft, vel = 0, lastX, raf;

    c.addEventListener('mousedown', e => {
    isDown = true; c.classList.add('dragging');
    startX = e.pageX - c.offsetLeft; scrollLeft = c.scrollLeft;
    cancelAnimationFrame(raf); vel = 0; lastX = e.pageX;
    });
    document.addEventListener('mouseup', () => {
    if (!isDown) return; isDown = false; c.classList.remove('dragging');
    let momentum = vel;
    function glide() {
        if (Math.abs(momentum) < 0.5) return;
        c.scrollLeft += momentum; momentum *= 0.93;
        infiniteReset();
        raf = requestAnimationFrame(glide);
    }
    glide();
    });
    c.addEventListener('mousemove', e => {
    if (!isDown) return; e.preventDefault();
    vel = (e.pageX - lastX) * -1.2; lastX = e.pageX;
    const x = e.pageX - c.offsetLeft;
    c.scrollLeft = scrollLeft - (x - startX) * 1.4;
    infiniteReset();
    });

    let touchX, touchScroll;
    c.addEventListener('touchstart', e => {
    touchX = e.touches[0].pageX; touchScroll = c.scrollLeft;
    }, {passive:true});
    c.addEventListener('touchmove', e => {
    c.scrollLeft = touchScroll + (touchX - e.touches[0].pageX);
    infiniteReset();
    }, {passive:true});

    function infiniteReset() {
    const half = c.scrollWidth / 2;
    if (c.scrollLeft >= half) c.scrollLeft -= half;
    if (c.scrollLeft <= 0 && c.scrollWidth > 0) c.scrollLeft += half;
    }
    c.addEventListener('scroll', infiniteReset, {passive:true});

    requestAnimationFrame(() => {
    c.scrollLeft = (c.scrollWidth / 2) - (c.offsetWidth / 2);
    });

    let auto = null;
    function startAuto() { auto = setInterval(() => { if (!isDown) { c.scrollLeft += 0.7; infiniteReset(); }}, 20); }
    function stopAuto()  { clearInterval(auto); }
    startAuto();
    c.addEventListener('mouseenter', stopAuto);
    c.addEventListener('touchstart', stopAuto, {passive:true});
    c.addEventListener('mouseleave', startAuto);
    c.addEventListener('touchend', () => setTimeout(startAuto, 1500));
}

// ── IntersectionObserver: reveal + card stagger ───────────────
const obs = new IntersectionObserver((entries) => {
    const groups = new Map();
    entries.forEach(e => {
    if (!e.isIntersecting) return;
    const parent = e.target.parentElement;
    if (!groups.has(parent)) groups.set(parent, []);
    groups.get(parent).push(e.target);
    obs.unobserve(e.target);
    });
    groups.forEach(els => {
    els.forEach((el, i) => setTimeout(() => el.classList.add('visible'), i * 80));
    });
}, { threshold: 0.06, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.wk-reveal, .wk-fi-card').forEach(el => obs.observe(el));

// ── GSAP: richer scroll effects ───────────────────────────────
if (window.gsap && window.ScrollTrigger) {
    gsap.registerPlugin(ScrollTrigger);

    gsap.utils.toArray('.wk-sh').forEach(el => {
    gsap.from(el, {
        y: 32, opacity: 0, duration: 0.7, ease: 'power2.out',
        scrollTrigger: { trigger: el, start: 'top 85%', toggleActions: 'play none none none' }
    });
    });

    const heroOrbs = document.querySelectorAll('.wk-hero-orb');
    heroOrbs.forEach((orb, i) => {
    gsap.to(orb, {
        y: (i % 2 === 0 ? -120 : -60), ease: 'none',
        scrollTrigger: { trigger: '.wk-hero', start: 'top top', end: 'bottom top', scrub: true }
    });
    });

    gsap.utils.toArray('.wk-compare > div').forEach((el, i) => {
    gsap.from(el, {
        y: 40, opacity: 0, duration: 0.6, delay: i * 0.15, ease: 'power2.out',
        scrollTrigger: { trigger: el, start: 'top 88%', toggleActions: 'play none none none' }
    });
    });

    document.querySelectorAll('.wk-count-up').forEach(el => {
    const target = parseFloat(el.dataset.target);
    const isInt = Number.isInteger(target);
    ScrollTrigger.create({
        trigger: el,
        start: 'top 85%',
        once: true,
        onEnter: () => {
        gsap.to({ v: 0 }, {
            v: target, duration: 1.4, ease: 'power2.out',
            onUpdate: function() {
            el.textContent = isInt ? Math.round(this.targets()[0].v) : this.targets()[0].v.toFixed(1);
            }
        });
        }
    });
    });
}

// ── Panel mockup tab interaction ──────────────────────────────
document.querySelectorAll('.wk-pm-nav-item').forEach(item => {
    item.addEventListener('click', function() {
    document.querySelectorAll('.wk-pm-nav-item').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
    });
});

// ── Persona tabs — handled by Alpine x-data wrapper above ─────

// Calculator is fully handled by Alpine.js x-data on the section element — no vanilla JS needed

});
</script>
@filamentScripts
    All data driven — swap $release, $aptitudes, $modules, $changelog,
    $previousReleases, $upcomingReleases from the controller.
--}}
@php
use Carbon\Carbon;

$release = [
    'version'     => defined('WEBKERNEL_VERSION')     ? WEBKERNEL_VERSION     : '1.3.32',
    'semver'      => defined('WEBKERNEL_SEMVER')       ? WEBKERNEL_SEMVER      : '1.3.32+53',
    'codename'    => defined('WEBKERNEL_CODENAME')     ? WEBKERNEL_CODENAME    : 'Waterfall',
    'channel'     => defined('WEBKERNEL_CHANNEL')      ? WEBKERNEL_CHANNEL     : 'stable',
    'released_at' => defined('WEBKERNEL_RELEASED_AT')  ? WEBKERNEL_RELEASED_AT : '2026-03-21',
    'commit'      => defined('WEBKERNEL_COMMIT')       ? WEBKERNEL_COMMIT      : 'a3f9c12',
    'branch'      => defined('WEBKERNEL_BRANCH')       ? WEBKERNEL_BRANCH      : 'main',
    'requires'    => defined('WEBKERNEL_REQUIRES')     ? WEBKERNEL_REQUIRES    : [],
    'compatible'  => defined('WEBKERNEL_COMPATIBLE_WITH') ? WEBKERNEL_COMPATIBLE_WITH : [],
    'early_discount' => 20,
    'early_deadline' => '2026-04-15',
    'founding_slots' => 3,
    'founding_taken' => 1,
];

$odrOffer = [
    'label'    => 'Waterfall Early Access',
    'discount' => $release['early_discount'],
    'deadline' => $release['early_deadline'],
    'slots'    => $release['founding_slots'],
    'taken'    => $release['founding_taken'],
    'savings'  => '10 000 MAD',
    'tagline'  => 'Reserve your Waterfall deployment before April 15 and lock the founding price permanently.',
];

$aptitudes = [
    [
        'icon'  => 'heroicon-o-cpu-chip',
        'tag'   => 'System',
        'name'  => 'WebernelAPI',
        'desc'  => 'A unified API that gives your entire application real-time access to system health — server load, memory, disk, and instance identity — through a single type-safe interface.',
        'badge' => 'Core',
        'color' => 'info',
    ],
    [
        'icon'  => 'heroicon-o-shield-check',
        'tag'   => 'Security',
        'name'  => 'Seal Enforcer',
        'desc'  => 'The software you deploy is exactly the software we signed. Any tampered file triggers an exception before a single request is served. No exceptions.',
        'badge' => 'Core',
        'color' => 'success',
    ],
    [
        'icon'  => 'heroicon-o-puzzle-piece',
        'tag'   => 'Modules',
        'name'  => 'Module Orchestrator',
        'desc'  => 'Install, activate, and remove business modules without touching the core. Dependencies are resolved automatically. Zero configuration.',
        'badge' => 'Core',
        'color' => 'info',
    ],
    [
        'icon'  => 'heroicon-o-squares-2x2',
        'tag'   => 'Panel',
        'name'  => 'System Panel',
        'desc'  => 'A native administration interface giving full visibility over your deployment — live metrics, module management, maintenance controls, and access rights.',
        'badge' => 'Included',
        'color' => 'warning',
    ],
    [
        'icon'  => 'heroicon-o-lock-closed',
        'tag'   => 'Access',
        'name'  => 'RBAC Engine',
        'desc'  => 'Every action in the system is gated by role. Owner, Admin, Developer, Viewer. The backend enforces it — the UI simply reflects it.',
        'badge' => 'Core',
        'color' => 'danger',
    ],
    [
        'icon'  => 'heroicon-o-photo',
        'tag'   => 'Interface',
        'name'  => 'Icon & Theme Registry',
        'desc'  => '4 600+ icons from open-source sets, a per-user theming system, and CSS injection hooks for every module. Your brand, your interface.',
        'badge' => 'Included',
        'color' => 'info',
    ],
    [
        'icon'  => 'heroicon-o-arrow-path',
        'tag'   => 'Reliability',
        'name'  => 'Auto-Update Engine',
        'desc'  => 'Signed update bundles are verified cryptographically before application. If verification fails, the update is rejected. Your system never runs untrusted code.',
        'badge' => 'Core',
        'color' => 'success',
    ],
    [
        'icon'  => 'heroicon-o-heart',
        'tag'   => 'Uptime',
        'name'  => 'Heartbeat & Integrity',
        'desc'  => 'Continuous health signaling to the Cloud control plane. Degraded mode activates automatically when connectivity is interrupted — your system keeps running.',
        'badge' => 'Core',
        'color' => 'info',
    ],
];

$modules = [
    ['name' => 'Invoicing',        'tag' => 'Finance',      'price' => '3 500',  'desc' => 'Issue invoices, track payments, generate PDFs, manage clients. Mobile PWA included.',        'compatible' => ['1.2+', 'Waterfall']],
    ['name' => 'Calendars & ICS',  'tag' => 'Scheduling',   'price' => '1 900',  'desc' => 'Sync with Google Calendar, Apple Calendar, or any ICS client. Zero dependency on their accounts.', 'compatible' => ['1.3+', 'Waterfall']],
    ['name' => 'Kanban Boards',    'tag' => 'Productivity', 'price' => '2 500',  'desc' => 'Drag-and-drop project and task management. Fully private to your organization.',            'compatible' => ['1.3+', 'Waterfall']],
    ['name' => 'Website Builder',  'tag' => 'Presence',     'price' => '4 900',  'desc' => 'Design and publish your company website from the same platform. You own the site.',          'compatible' => ['1.3+', 'Waterfall']],
    ['name' => 'CRM',              'tag' => 'Sales',        'price' => '3 900',  'desc' => 'Manage leads, clients, and pipelines. Full history, notes, and activity log per contact.',    'compatible' => ['Waterfall', 'upcoming']],
    ['name' => 'HR & Attendance',  'tag' => 'Operations',   'price' => '2 900',  'desc' => 'Employee directory, leave requests, attendance tracking. No external HRIS required.',         'compatible' => ['Waterfall', 'upcoming']],
    ['name' => 'Document Vault',   'tag' => 'Compliance',   'price' => '2 200',  'desc' => 'Secure document storage with versioning, access control, and audit trail.',                  'compatible' => ['Waterfall', 'upcoming']],
    ['name' => 'Analytics Board',  'tag' => 'Intelligence', 'price' => '3 200',  'desc' => 'Usage metrics, system performance, task velocity — visualized inside your System Panel.',    'compatible' => ['Waterfall', 'upcoming']],
];

$changelog = [
    'added' => [
        'Release data stamped automatically from composer.lock — PHP, Laravel, and Filament versions are never hardcoded',
        'Makefile.php release tool with keygen, TTL-based keys, interactive codename and channel selection',
        'Full git integration in the release tool — commit, tag, and push bootstrap/ in a single flow',
        'WEBKERNEL_REQUIRES and WEBKERNEL_COMPATIBLE_WITH constants derived from installed dependencies',
        'platform/assessors/constants/ directory — paths, registry, runtime, thresholds, security, globals separated',
        'WEBKERNEL_MODULE_REGISTRIES constant with multi-registry support: webkernelphp.com, github.com, gitlab.com, git.numerimondes.com',
        'IS_DEVMODE now sourced from dev-tools.php alongside dev namespace map',
        'Dev namespace map allows arbitrary Webkernel\\ sub-namespaces loaded only in dev mode',
        'Legal warning with confirm prompt before any release operation — integrity notice with license reference',
    ],
    'changed' => [
        'bootstrap/webkernel/ restructured into three top-level folders: src/, platform/, runtime/',
        'All constants moved from fast-boot.php into platform/assessors/constants/ split by concern',
        'SVG assets moved to runtime/dist/svg/ — dist/ is the new static asset root',
        'AppModels and compiled signed code moved to runtime/static/',
        'WEBKERNEL_HELPERS_ROOT now points to platform/assessors/system/src/helpers',
        'Autoloader PSR-4 map uses array_merge with WEBKERNEL_DEV_NAMESPACES — Octane-safe',
        'fast-boot.php now requires webkernel-constants.php from same directory — single include chain from bootstrap/app.php',
    ],
    'security' => [
        'All git calls in Makefile.php use Symfony\Component\Process — no shell_exec, no string interpolation',
        'Keygen uses openssl_random_pseudo_bytes(32) — cryptographically strong 64-char hex key',
        'Build key stored in storage/webkernel/cache/.build-token with chmod 0600',
        'hash_equals() used for key comparison — timing-attack resistant',
        'Key is single-use by default — deleted immediately after validation unless --keep is passed',
        'assertFoundationRepo() blocks execution if bootstrap/ remote is not github.com/webkernelphp/foundation',
    ],
    'fixed' => [
        'Git commit hash was resolving as "unknown" — all git() calls now use BOOTSTRAP_DIR as working directory',
        'WEBKERNEL_COMPATIBLE_WITH php minimum was falling back to 8.2.0 even when Laravel 13 requires 8.3 — now derived from package constraints in composer.lock',
        'Duplicate semicolons in stamped fast-boot.php caused by preg_replace matching across the defined() guard',
    ],
];

$previousReleases = [
    [
        'version'    => '1.2.x',
        'codename'   => 'Horizon',
        'date'       => '2025-11-10',
        'status'     => 'lts',
        'highlights' => ['Module Orchestrator v1', 'Ed25519 update signing', 'System Panel RBAC'],
    ],
    [
        'version'    => '1.1.x',
        'codename'   => 'Meridian',
        'date'       => '2025-07-03',
        'status'     => 'eol',
        'highlights' => ['WebernelAPI v1', 'CoreManifest integrity', 'Filament 3 System Panel'],
    ],
    [
        'version'    => '1.0.x',
        'codename'   => 'Genesis',
        'date'       => '2025-02-14',
        'status'     => 'eol',
        'highlights' => ['Initial public release', 'Bootstrap architecture', 'EPL-2.0 open core'],
    ],
];

$upcomingReleases = [
    [
        'codename'    => 'Solstice',
        'version'     => '1.4.x',
        'eta'         => 'Q3 2026',
        'status'      => 'planned',
        'teaser'      => 'AI-layer integration hooks, PostgreSQL sensitivity attributes, multi-tenant isolation engine.',
    ],
    [
        'codename'    => 'Vanguard',
        'version'     => '2.0.x',
        'eta'         => 'Q1 2027',
        'status'      => 'roadmap',
        'teaser'      => 'Webkernel OS foundation, sovereign hardware partner program, full offline verification suite.',
    ],
];

$sectors = [
    ['icon' => '🏢', 'label' => 'SMEs & family businesses'],
    ['icon' => '🏫', 'label' => 'Schools & universities'],
    ['icon' => '🏥', 'label' => 'Healthcare & clinics'],
    ['icon' => '🏦', 'label' => 'Finance & accounting'],
    ['icon' => '🏛️', 'label' => 'Government & public bodies'],
    ['icon' => '⚖️', 'label' => 'Legal & consulting firms'],
    ['icon' => '🚚', 'label' => 'Logistics & manufacturing'],
    ['icon' => '🌍', 'label' => 'NGOs & humanitarian aid'],
    ['icon' => '🔒', 'label' => 'Critical infrastructure'],
    ['icon' => '✈️', 'label' => 'Aviation & aerospace'],
    ['icon' => '💊', 'label' => 'Pharmaceuticals'],
    ['icon' => '🛡️', 'label' => 'Defense & security'],
];

$pricing = [
    [
        'tier'     => 'Foundation',
        'price'    => 'Free',
        'period'   => 'Open-source · forever',
        'featured' => false,
        'color'    => 'gray',
        'features' => [
            'Webkernel Core — EPL-2.0 license',
            'System administration panel',
            'Module orchestration engine',
            'Deploy on your own infrastructure',
            'Community support via GitHub',
        ],
        'cta'      => 'Get started',
        'href'     => 'https://github.com/webkernelphp/foundation',
    ],
    [
        'tier'     => 'Business',
        'price'    => 'From 1 900 MAD',
        'period'   => 'Per module · one-time purchase',
        'featured' => true,
        'color'    => 'primary',
        'features' => [
            'All Foundation features',
            'Any first-party module — purchased once, owned permanently',
            'Annual updates optional — never more than 25% of original price',
            'Priority email support',
            'Early access to new modules',
            'Founding offer: save 10 000 MAD (3 slots remaining · expires April 2026)',
        ],
        'cta'      => 'Reserve your slot',
        'href'     => '#contact',
    ],
    [
        'tier'     => 'Sovereign',
        'price'    => 'On request',
        'period'   => 'Government & critical infrastructure',
        'featured' => false,
        'color'    => 'gray',
        'features' => [
            'All Business features',
            'Fully air-gapped deployment',
            'Local integrity verification — zero outbound connection',
            'Dedicated support contract with SLA',
            'Offline update delivery',
            'Compliance documentation package',
        ],
        'cta'      => 'Contact Numerimondes',
        'href'     => '#contact',
    ],
];

$problems = [
    ['label' => 'Monthly fees that compound every year, forever',               'cost' => '~$50K / 5 yr for 5 tools'],
    ['label' => 'Your data on someone else\'s servers, under someone else\'s terms', 'cost' => 'Compliance risk'],
    ['label' => 'Features removed or paywalled after you\'ve built around them', 'cost' => 'Vendor leverage'],
    ['label' => 'A vendor who can raise prices because you\'re locked in',       'cost' => '15–40% YoY price hikes'],
    ['label' => 'No software access if you miss one payment',                   'cost' => 'Operational risk'],
    ['label' => 'Compliance failures when data crosses borders involuntarily',  'cost' => 'GDPR / sector risk'],
];
$solutions = [
    ['label' => 'Buy once, own forever — no recurring fees',                      'proof' => 'Zero subscriptions'],
    ['label' => 'Deploy on your server, in your country, on your terms',          'proof' => '100% data residency'],
    ['label' => 'Open-source core — inspect every line, forever',                 'proof' => 'EPL-2.0 on GitHub'],
    ['label' => 'Annual updates capped at 25% of original price, and optional',  'proof' => 'Contract-protected'],
    ['label' => 'Air-gapped and fully offline for regulated environments',        'proof' => 'Sovereign tier'],
    ['label' => 'Built-in GDPR, healthcare, and government compliance posture',  'proof' => 'RBAC + audit log'],
];

$useCases = [
    [
        'icon'    => '🏢',
        'persona' => 'Enterprise',
        'title'   => 'Replace 5 SaaS tools with one platform',
        'desc'    => 'CRM, invoicing, HR, document vault, and analytics — all running on your own server. No data leaving your network. No monthly invoices.',
        'metric'  => 'Save 40–80K MAD / year',
        'color'   => 'blue',
    ],
    [
        'icon'    => '🏥',
        'persona' => 'Healthcare',
        'title'   => 'Patient data that never leaves the premises',
        'desc'    => 'Fully air-gapped deployment. No outbound connections. Integrity-verified at every boot. Designed for environments where data residency is non-negotiable.',
        'metric'  => 'Zero cloud dependency',
        'color'   => 'teal',
    ],
    [
        'icon'    => '🏛️',
        'persona' => 'Government',
        'title'   => 'Critical infrastructure with full auditability',
        'desc'    => 'Every file cryptographically signed. Every action role-gated and logged. Offline update delivery. Compliance documentation included.',
        'metric'  => 'Sovereign tier available',
        'color'   => 'amber',
    ],
    [
        'icon'    => '🚀',
        'persona' => 'SME',
        'title'   => 'Launch internal operations in days, not months',
        'desc'    => 'Install ready-made modules, customize your interface, and deploy on shared hosting or a VPS. No dev team required for standard operations.',
        'metric'  => 'Operational in < 48h',
        'color'   => 'indigo',
    ],
];
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-smooth antialiased dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Webkernel™ {{ $release['semver'] }} "{{ ucfirst($release['codename']) }}" — Own Your Infrastructure</title>
<meta name="description" content="Webkernel™ {{ $release['semver'] }} — the self-hosted application platform for organizations that cannot afford vendor lock-in. Buy once. Deploy anywhere. Own everything.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
@filamentStyles
    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}
<style>
/* x-cloak — hide Alpine-controlled elements before init */
[x-cloak] { display: none !important; }

/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; }
html { font-size: 16px; }

/* ── Design tokens ── */
:root {
    --bg:       #08090c;
    --bg-1:     #0d0f18;
    --bg-2:     #121520;
    --bg-3:     #181c2c;
    --border:   rgba(255,255,255,0.06);
    --border-2: rgba(255,255,255,0.11);
    --border-3: rgba(255,255,255,0.18);

    --blue:     #3b82f6;
    --blue-l:   #60a5fa;
    --blue-xl:  #93c5fd;
    --blue-d:   #1d4ed8;
    --blue-bg:  rgba(59,130,246,0.09);
    --blue-bg2: rgba(59,130,246,0.16);

    --indigo:   #6366f1;
    --indigo-l: #a5b4fc;
    --indigo-bg:rgba(99,102,241,0.08);

    --teal:     #14b8a6;
    --teal-l:   #5eead4;
    --teal-bg:  rgba(20,184,166,0.09);

    --amber:    #f59e0b;
    --amber-l:  #fcd34d;
    --amber-bg: rgba(245,158,11,0.09);
    --amber-bright: #fbbf24;

    --green:    #22c55e;
    --red:      #ef4444;

    --text:     #eef0f8;
    --text-2:   rgba(238,240,248,0.68);
    --text-3:   rgba(238,240,248,0.38);
    --text-4:   rgba(238,240,248,0.18);

    --serif: 'Cormorant Garamond', Georgia, serif;
    --sans:  'DM Sans', system-ui, sans-serif;
    --mono:  'DM Mono', monospace;

    --r:  8px;
    --r2: 16px;
    --r3: 24px;
    --sp: 6.5rem;
    --mw: 1280px;

    --shadow-card:     0 1px 3px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.04);
    --shadow-elevated: 0 8px 32px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.06);
    --shadow-glow-blue:0 0 40px rgba(59,130,246,.18);
    --glass:        rgba(18,21,32,.68);
    --glass-border: rgba(255,255,255,.09);
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    font-size: 1rem;
    line-height: 1.7;
    -webkit-font-smoothing: antialiased;
    overflow-x: hidden;
    font-feature-settings: "kern" 1, "liga" 1, "calt" 1;
}

body::after {
    content: '';
    position: fixed; inset: 0;
    pointer-events: none; z-index: 9998;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
    opacity: .5;
}

.wk-progress {
    position: fixed; top: 0; left: 0; z-index: 400;
    height: 2px; width: 0%;
    background: linear-gradient(90deg, var(--blue-d), var(--blue), var(--teal-l));
    box-shadow: 0 0 10px var(--blue-l);
    transition: width .05s linear;
}

.wk-cursor {
    position: fixed; pointer-events: none; z-index: 9997;
    width: 360px; height: 360px; border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,.05) 0%, transparent 70%);
    transform: translate(-50%,-50%);
    transition: opacity .3s;
    opacity: 0;
}

.wk-orb {
    position: absolute; border-radius: 50%;
    pointer-events: none; will-change: transform;
    filter: blur(1px);
}

.wk-c  { max-width: var(--mw); margin: 0 auto; padding: 0 1.875rem; }
.wk-s  { border-bottom: 1px solid var(--border); }

/* ── Navigation ── */
.wk-nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 300;
    height: 56px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 1.875rem;
    background: rgba(8,9,12,.88);
    backdrop-filter: blur(24px) saturate(1.8);
    border-bottom: 1px solid var(--border);
}
.wk-logo { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
.wk-logo-text { font-family: var(--sans); font-size: .95rem; font-weight: 600; color: var(--text); letter-spacing: .01em; }
.wk-logo-badge {
    font-family: var(--mono); font-size: .62rem; letter-spacing: .1em;
    text-transform: uppercase; color: var(--blue-l);
    background: var(--blue-bg); border: 1px solid rgba(96,165,250,.22);
    padding: .18rem .65rem; border-radius: 20px;
}
.wk-nav-links { display: flex; gap: .125rem; list-style: none; margin: 0; padding: 0; }
.wk-nav-links a {
    padding: .375rem .85rem; color: var(--text-3); text-decoration: none;
    font-size: .84rem; border-radius: 6px;
    transition: color .15s, background .15s;
}
.wk-nav-links a:hover { color: var(--text); background: rgba(255,255,255,.05); }
.wk-nav-right { display: flex; gap: .625rem; align-items: center; }

/* ── Announcement banner ── */
.wk-banner {
    margin-top: 56px; padding: .625rem 1.875rem;
    background: linear-gradient(90deg, #1a2e6e 0%, #0f1d4a 50%, #1a2e6e 100%);
    border-bottom: 1px solid rgba(59,130,246,.22);
    display: flex; align-items: center; justify-content: center; gap: 1.25rem; flex-wrap: wrap;
}
.wk-banner-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--blue-xl); flex-shrink: 0;
    box-shadow: 0 0 10px var(--blue-l);
    animation: pulse 2.2s ease-in-out infinite;
}
@keyframes pulse { 0%,100% { opacity:1; box-shadow:0 0 10px var(--blue-l); } 50% { opacity:.4; box-shadow:0 0 2px var(--blue); } }
.wk-banner-t { font-family: var(--mono); font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; color: #bfdbfe; }
.wk-banner-t strong { color: #fff; font-weight: 500; }
.wk-banner-sep { color: rgba(255,255,255,.2); }
.wk-banner-btn {
    font-size: .75rem; font-weight: 700; color: var(--bg);
    background: linear-gradient(135deg, var(--blue-xl), #e0f2fe);
    padding: .3rem 1rem; border-radius: 20px;
    text-decoration: none; transition: opacity .15s, transform .15s;
    box-shadow: 0 0 16px rgba(147,197,253,.3);
}
.wk-banner-btn:hover { opacity: .92; transform: translateY(-1px); }

/* ═══════════════════════════════════════════════════════════
   HERO — enterprise-grade, direct, specific
═══════════════════════════════════════════════════════════ */
.wk-hero {
    padding: calc(var(--sp) * 2.2) 0 calc(var(--sp) * 1.4);
    position: relative; overflow: hidden;
    min-height: 96vh; display: flex; align-items: center;
}

.wk-hero-orb-1 {
    top: -180px; left: -120px;
    width: 960px; height: 960px;
    background: radial-gradient(circle, rgba(29,78,216,.22) 0%, rgba(59,130,246,.06) 40%, transparent 68%);
}
.wk-hero-orb-2 {
    bottom: -80px; right: -160px;
    width: 720px; height: 720px;
    background: radial-gradient(circle, rgba(20,184,166,.07) 0%, transparent 60%);
}
.wk-hero-orb-3 {
    top: 30%; left: 42%;
    width: 480px; height: 480px;
    background: radial-gradient(circle, rgba(99,102,241,.06) 0%, transparent 65%);
}

.wk-hero-mesh {
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(ellipse 700px 400px at 15% 55%, rgba(29,78,216,.09) 0%, transparent 70%),
        radial-gradient(ellipse 450px 650px at 82% 18%, rgba(20,184,166,.04) 0%, transparent 70%);
}

.wk-hero-grid {
    position: absolute; inset: 0; pointer-events: none;
    background-image:
        linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black, transparent);
}

.wk-hero-inner { position: relative; z-index: 2; width: 100%; }

.wk-hero-layout {
    display: grid;
    grid-template-columns: 1fr 480px;
    gap: 5rem;
    align-items: center;
}

.wk-eyebrow {
    display: inline-flex; align-items: center; gap: .75rem;
    font-family: var(--mono); font-size: .68rem; letter-spacing: .14em;
    text-transform: uppercase; color: var(--blue-l); margin-bottom: 2.25rem;
}
.wk-eyebrow-line { width: 28px; height: 1px; background: var(--blue); opacity: .5; }

.wk-hero-h1 {
    font-size: clamp(2.75rem, 5.5vw, 4.75rem);
    margin-bottom: 1.75rem;
}
.wk-hero-sub {
    max-width: 520px; color: var(--text-2);
    font-size: 1.175rem; line-height: 1.85;
    margin-bottom: 2.75rem; font-weight: 300;
}
.wk-hero-actions {
    display: flex; gap: .75rem; flex-wrap: wrap; align-items: center;
    margin-bottom: 2.75rem;
}

.wk-hero-meta {
    display: flex; gap: 2.25rem; flex-wrap: wrap;
    padding-top: 1.75rem; border-top: 1px solid var(--border);
}
.wk-hero-meta-item {
    font-family: var(--mono); font-size: .67rem;
    color: var(--text-3); letter-spacing: .06em;
}
.wk-hero-meta-item strong { color: var(--blue-l); font-weight: 500; }

/* Hero trust signal strip (logos / indicators) */
.wk-trust-strip {
    display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;
    padding: 1.25rem 0 0; margin-top: 1.5rem;
    border-top: 1px solid var(--border);
}
.wk-trust-item {
    display: flex; align-items: center; gap: .5rem;
    font-family: var(--mono); font-size: .64rem;
    color: var(--text-3); letter-spacing: .06em;
}
.wk-trust-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.wk-trust-dot-green  { background: var(--green);  box-shadow: 0 0 4px var(--green); }
.wk-trust-dot-blue   { background: var(--blue-l); box-shadow: 0 0 4px var(--blue-l); }
.wk-trust-dot-amber  { background: var(--amber-bright); box-shadow: 0 0 4px var(--amber-l); }

/* ── Right panel mockup ── */
.wk-hero-panel {
    background: var(--glass);
    border: 1px solid var(--glass-border);
    border-radius: var(--r2);
    overflow: hidden;
    box-shadow:
        0 40px 80px rgba(0,0,0,.65),
        0 0 0 1px rgba(255,255,255,.06),
        0 0 60px rgba(59,130,246,.08),
        inset 0 1px 0 rgba(255,255,255,.06);
    backdrop-filter: blur(16px);
    transform: perspective(1400px) rotateY(-6deg) rotateX(2.5deg);
    transition: transform .5s cubic-bezier(.23,1,.32,1), box-shadow .5s;
    will-change: transform;
}
.wk-hero-panel:hover {
    transform: perspective(1400px) rotateY(-2deg) rotateX(0.5deg);
    box-shadow:
        0 48px 100px rgba(0,0,0,.7),
        0 0 0 1px rgba(255,255,255,.08),
        0 0 80px rgba(59,130,246,.12),
        inset 0 1px 0 rgba(255,255,255,.07);
}

.wk-pm-bar {
    padding: .625rem 1rem; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: .5rem;
    background: rgba(8,9,12,.7);
}
.wk-pm-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.wk-pm-dot-r { background: #ff5f57; }
.wk-pm-dot-y { background: #febc2e; }
.wk-pm-dot-g { background: #28c840; }
.wk-pm-title { font-family: var(--mono); font-size: .62rem; color: var(--text-3); margin-left: auto; letter-spacing: .05em; }

.wk-pm-body { display: flex; min-height: 300px; }

.wk-pm-sidebar {
    width: 140px; flex-shrink: 0;
    border-right: 1px solid var(--border);
    padding: .625rem 0;
    background: rgba(8,9,12,.5);
}
.wk-pm-sidebar-logo {
    padding: .625rem 1rem 1rem;
    font-family: var(--mono); font-size: .7rem; font-weight: 500;
    color: var(--blue-l); letter-spacing: .05em;
    border-bottom: 1px solid var(--border);
    margin-bottom: .375rem;
}
.wk-pm-nav-item {
    padding: .45rem 1rem; font-size: .74rem; color: var(--text-3);
    cursor: pointer; border-left: 2px solid transparent;
    transition: color .12s, background .12s, border-color .12s;
    display: flex; align-items: center; gap: .5rem;
}
.wk-pm-nav-item:hover { color: var(--text-2); background: rgba(255,255,255,.03); }
.wk-pm-nav-item.active { color: var(--blue-l); border-left-color: var(--blue); background: var(--blue-bg); }
.wk-pm-nav-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--text-4); flex-shrink: 0;
}
.wk-pm-nav-item.active .wk-pm-nav-dot { background: var(--blue-l); box-shadow: 0 0 4px var(--blue-l); }

.wk-pm-content { flex: 1; padding: 1rem; overflow: hidden; }

.wk-pm-stats-row {
    display: grid; grid-template-columns: repeat(4,1fr); gap: .5rem; margin-bottom: 1rem;
}
.wk-pm-stat {
    background: rgba(255,255,255,.03); border: 1px solid var(--border);
    border-radius: 7px; padding: .5rem .625rem; text-align: center;
}
.wk-pm-stat-val {
    font-family: var(--mono); font-size: .875rem; font-weight: 500; line-height: 1.1;
}
.wk-pm-stat-key { font-family: var(--mono); font-size: .57rem; color: var(--text-4); margin-top: .15rem; letter-spacing: .05em; }
.wk-pm-stat-info    .wk-pm-stat-val { color: var(--blue-l); }
.wk-pm-stat-success .wk-pm-stat-val { color: #4ade80; }
.wk-pm-stat-warning .wk-pm-stat-val { color: var(--amber-bright); }
.wk-pm-stat-teal    .wk-pm-stat-val { color: var(--teal-l); }

.wk-pm-integrity {
    display: flex; align-items: center; gap: .625rem;
    padding: .5rem .75rem; border-radius: 6px;
    background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2);
    margin-bottom: 1rem;
}
.wk-pm-integrity-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #4ade80; box-shadow: 0 0 5px #4ade80;
    animation: pulse 2s infinite; flex-shrink: 0;
}
.wk-pm-integrity-text { font-family: var(--mono); font-size: .63rem; color: #86efac; letter-spacing: .04em; }

.wk-pm-mod-list { display: flex; flex-direction: column; gap: .3rem; }
.wk-pm-mod-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .45rem .7rem;
    background: rgba(255,255,255,.025); border: 1px solid var(--border);
    border-radius: 5px; font-size: .72rem;
}
.wk-pm-mod-name { color: var(--text-2); }
.wk-pm-mod-status { font-family: var(--mono); font-size: .62rem; }
.wk-pm-mod-status.active { color: #4ade80; }
.wk-pm-mod-status.update { color: var(--amber-bright); }

.wk-pm-chart-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: 1rem;
}
.wk-pm-mini-chart {
    background: rgba(255,255,255,.025); border: 1px solid var(--border);
    border-radius: 6px; padding: .625rem .75rem; font-size: .7rem;
}
.wk-pm-mini-chart-label { color: var(--text-3); font-family: var(--mono); font-size: .58rem; margin-bottom: .3rem; letter-spacing: .04em; }
.wk-pm-mini-chart-bar {
    height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; margin-bottom: .25rem;
}
.wk-pm-mini-chart-fill { height: 100%; border-radius: 2px; }
.wk-pm-mini-chart-val { font-family: var(--mono); font-size: .7rem; }

.wk-code-box {
    background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r2); overflow: hidden;
}
.wk-code-bar {
    padding: .6rem 1rem; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: .5rem;
}
.wk-code-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--border-2); }
.wk-code-title { font-family: var(--mono); font-size: .65rem; color: var(--text-3); letter-spacing: .06em; margin-left: auto; }
.wk-code-body {
    padding: 1.25rem 1.5rem; font-family: var(--mono); font-size: .8rem;
    line-height: 1.9; color: var(--text-2); overflow-x: auto;
}
.wk-code-key { color: var(--blue-xl); }
.wk-code-val { color: #a5f3fc; }
.wk-code-str { color: #86efac; }

.wk-scroll-hint {
    position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%);
    display: flex; flex-direction: column; align-items: center; gap: .375rem;
    font-family: var(--mono); font-size: .62rem; color: var(--text-4);
    letter-spacing: .1em; text-transform: uppercase;
    animation: wkFU 1s ease 1s both;
}
.wk-scroll-arrow { width: 20px; height: 30px; border: 1px solid var(--border-2); border-radius: 10px; position: relative; }
.wk-scroll-arrow::after {
    content: ''; position: absolute; top: 6px; left: 50%; transform: translateX(-50%);
    width: 3px; height: 6px; background: var(--blue-l); border-radius: 2px;
    animation: wkScrollDot 1.8s ease-in-out infinite;
}
@keyframes wkScrollDot { 0% { transform: translateX(-50%) translateY(0); opacity: 1; } 100% { transform: translateX(-50%) translateY(12px); opacity: 0; } }

@keyframes wkFU { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
.a1 { animation: wkFU .5s ease .08s both; }
.a2 { animation: wkFU .55s ease .18s both; }
.a3 { animation: wkFU .55s ease .3s both; }
.a4 { animation: wkFU .55s ease .44s both; }
.a5 { animation: wkFU .55s ease .58s both; }

/* ═══════════════════════════════════════════════════════════
   ODR / EARLY ACCESS BLOCK
═══════════════════════════════════════════════════════════ */
.wk-odr {
    background: linear-gradient(135deg, rgba(29,78,216,.38) 0%, rgba(17,37,84,.6) 50%, rgba(18,21,32,.82) 100%);
    border: 1px solid rgba(59,130,246,.28);
    border-radius: var(--r2);
    padding: 3.25rem;
    margin: var(--sp) 0;
    display: grid; grid-template-columns: 1fr auto;
    gap: 2rem; align-items: center;
    position: relative; overflow: hidden;
    backdrop-filter: blur(20px);
    box-shadow: 0 28px 56px rgba(0,0,0,.45), 0 0 0 1px rgba(59,130,246,.1), inset 0 1px 0 rgba(255,255,255,.06);
}
.wk-odr::before {
    content: ''; position: absolute; top: -80px; right: -80px;
    width: 320px; height: 320px;
    background: radial-gradient(circle, rgba(59,130,246,.14) 0%, transparent 60%);
    pointer-events: none;
}
.wk-odr::after {
    content: ''; position: absolute; bottom: -40px; left: 30%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(20,184,166,.06) 0%, transparent 60%);
    pointer-events: none;
}
.wk-odr-ey {
    font-family: var(--mono); font-size: .67rem; letter-spacing: .14em;
    text-transform: uppercase; color: var(--blue-xl); margin-bottom: .875rem;
}
.wk-odr-h {
    font-family: var(--serif); font-size: clamp(1.85rem, 3.5vw, 2.9rem);
    font-weight: 300; line-height: 1.2; margin-bottom: 1.1rem;
}
.wk-odr-h em { font-style: italic; color: var(--blue-xl); }
.wk-odr-p { color: rgba(238,240,248,.7); font-size: .975rem; line-height: 1.75; max-width: 520px; margin-bottom: 1.75rem; }
.wk-odr-stats {
    min-width: 200px; text-align: center;
    padding: 2rem 2.25rem;
    background: rgba(8,9,12,.5); border-radius: var(--r2);
    border: 1px solid rgba(59,130,246,.22);
    display: flex; flex-direction: column; gap: 1.375rem; flex-shrink: 0;
    backdrop-filter: blur(8px);
}
.wk-odr-num { font-family: var(--serif); font-size: 3.5rem; font-weight: 300; color: var(--blue-xl); line-height: 1; }
.wk-odr-lbl { font-family: var(--mono); font-size: .62rem; letter-spacing: .1em; text-transform: uppercase; color: var(--text-3); }
.wk-odr-dots { display: flex; gap: .4rem; justify-content: center; margin-top: .3rem; }
.wk-odr-dot { width: 12px; height: 12px; border-radius: 50%; background: var(--border); border: 1px solid var(--border-2); }
.wk-odr-dot.taken { background: var(--blue); border-color: var(--blue-l); box-shadow: 0 0 8px rgba(59,130,246,.5); }

/* ═══════════════════════════════════════════════════════════
   SECTION TYPOGRAPHY
═══════════════════════════════════════════════════════════ */
.wk-sh { margin-bottom: 3.25rem; }
.wk-ey {
    font-family: var(--mono); font-size: .67rem; letter-spacing: .14em;
    text-transform: uppercase; color: var(--blue-l); margin-bottom: .875rem;
}
.wk-h2 {
    font-family: var(--sans); font-size: clamp(2.4rem, 4.5vw, 3.75rem);
    font-weight: 700; line-height: 1.06; letter-spacing: -.028em; margin-bottom: 1rem;
}
.wk-h2 em { font-family: var(--serif); font-style: italic; font-weight: 300; color: var(--blue-xl); font-size: 1.15em; }
.wk-lead { color: var(--text-2); font-size: 1rem; line-height: 1.82; max-width: 580px; }

/* ═══════════════════════════════════════════════════════════
   STATEMENT — sharper copy
═══════════════════════════════════════════════════════════ */
.wk-statement {
    padding: var(--sp) 0; text-align: center;
    background: linear-gradient(180deg, var(--bg) 0%, var(--bg-1) 100%);
}
.wk-statement-h {
    font-family: var(--sans); font-size: clamp(2.85rem, 5.5vw, 5rem);
    font-weight: 700; line-height: 1.05; letter-spacing: -.03em; margin-bottom: 1.5rem;
}
.wk-statement-h em { font-family: var(--serif); font-style: italic; font-weight: 300; color: var(--blue-xl); font-size: 1.08em; }
.wk-statement p { color: var(--text-2); font-size: 1.15rem; line-height: 1.82; max-width: 680px; margin: 0 auto; }

/* ═══════════════════════════════════════════════════════════
   STATS ROW
═══════════════════════════════════════════════════════════ */
.wk-stats-row {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 1px;
    background: var(--border);
    border: 1px solid var(--border); border-radius: var(--r2); overflow: hidden;
    margin: var(--sp) 0;
}
.wk-stat-cell {
    background: var(--bg-2); padding: 2.5rem 2rem; text-align: center;
    transition: background .18s;
}
.wk-stat-cell:hover { background: var(--bg-3); }
.wk-stat-num {
    font-family: var(--serif); font-size: clamp(2.5rem, 4vw, 4rem); font-weight: 300;
    color: var(--blue-xl); line-height: 1; margin-bottom: .5rem;
    text-shadow: 0 0 40px rgba(147,197,253,.15);
}
.wk-stat-num sup { font-size: .5em; vertical-align: super; }
.wk-stat-label { font-family: var(--mono); font-size: .67rem; color: var(--text-3); letter-spacing: .1em; text-transform: uppercase; }

/* ═══════════════════════════════════════════════════════════
   CARD GRIDS
═══════════════════════════════════════════════════════════ */
.wk-grid { display: grid; gap: 1px; background: var(--border); border: 1px solid var(--border); border-radius: var(--r2); overflow: hidden; }
.wk-grid-4 { grid-template-columns: repeat(4,1fr); }
.wk-grid-3 { grid-template-columns: repeat(3,1fr); }
.wk-grid-2 { grid-template-columns: repeat(2,1fr); }
.wk-grid-6 { grid-template-columns: repeat(6,1fr); }

.wk-card { background: var(--bg-2); padding: 1.75rem; transition: background .18s; }
.wk-card:hover { background: var(--bg-3); }

.wk-card-grid { display: grid; gap: 1rem; }
.wk-card-grid-4 { grid-template-columns: repeat(4,1fr); }
.wk-card-grid-3 { grid-template-columns: repeat(3,1fr); }

.wk-fi-card {
    background: var(--glass);
    border: 1px solid var(--glass-border);
    border-radius: var(--r2); padding: 1.625rem;
    box-shadow: var(--shadow-card);
    backdrop-filter: blur(10px);
    transition: border-color .22s, box-shadow .22s, transform .22s;
    opacity: 0; transform: translateY(20px);
}
.wk-fi-card.visible {
    opacity: 1; transform: none;
    transition: opacity .5s ease, transform .5s ease, border-color .22s, box-shadow .22s;
}
.wk-fi-card:hover {
    border-color: rgba(59,130,246,.28);
    box-shadow: var(--shadow-elevated), 0 0 0 1px rgba(59,130,246,.12);
    transform: translateY(-3px);
}
.wk-fi-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .875rem; }
.wk-apt-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: var(--blue-bg); border: 1px solid rgba(59,130,246,.18);
    display: flex; align-items: center; justify-content: center;
    color: var(--blue-l); flex-shrink: 0;
}
.wk-apt-tag { font-family: var(--mono); font-size: .61rem; letter-spacing: .1em; text-transform: uppercase; color: var(--blue); margin-bottom: .4rem; display: block; }
.wk-apt-name { font-family: var(--sans); font-size: 1.05rem; font-weight: 600; margin-bottom: .5rem; line-height: 1.2; }
.wk-apt-desc { color: var(--text-3); font-size: .85rem; line-height: 1.7; }

.wk-mod-tag { font-family: var(--mono); font-size: .61rem; letter-spacing: .1em; text-transform: uppercase; color: var(--blue); margin-bottom: .5rem; display: block; }
.wk-mod-name { font-family: var(--sans); font-size: 1.05rem; font-weight: 600; margin-bottom: .5rem; line-height: 1.2; }
.wk-mod-desc { color: var(--text-3); font-size: .85rem; line-height: 1.65; flex: 1; }
.wk-mod-foot { margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: flex-end; }
.wk-mod-price { font-family: var(--mono); font-size: .78rem; color: var(--text-3); }
.wk-mod-price strong { color: var(--blue-xl); font-size: .9rem; }
.wk-mod-tags { display: flex; gap: .3rem; flex-wrap: wrap; }
.wk-mod-ct {
    font-family: var(--mono); font-size: .58rem; letter-spacing: .05em;
    background: var(--blue-bg); color: var(--blue-xl);
    border: 1px solid rgba(59,130,246,.18); padding: .1rem .45rem; border-radius: 3px;
}
.wk-mod-ct.upcoming { background: var(--teal-bg); color: var(--teal-l); border-color: rgba(20,184,166,.22); }

/* ═══════════════════════════════════════════════════════════
   COMPARE — with costs column
═══════════════════════════════════════════════════════════ */
.wk-compare { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: start; }
.wk-compare-head {
    font-family: var(--mono); font-size: .67rem; letter-spacing: .12em;
    text-transform: uppercase; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .625rem;
}
.wk-compare-head.bad { color: #f87171; }
.wk-compare-head.good { color: var(--blue-l); }
.wk-clist { list-style: none; padding: 0; margin: 0; }
.wk-clist li {
    display: grid; grid-template-columns: auto 1fr auto;
    gap: .875rem; align-items: baseline;
    padding: .85rem 0; border-bottom: 1px solid var(--border);
    font-size: .925rem; line-height: 1.55;
}
.wk-clist li:last-child { border-bottom: none; }
.wk-clist.bad li { color: var(--text-3); }
.wk-clist.good li { color: var(--text-2); }
.ico-b { color: var(--red); flex-shrink: 0; font-size: .875rem; }
.ico-g { color: var(--blue-l); flex-shrink: 0; font-size: .8rem; }
.wk-clist-cost {
    font-family: var(--mono); font-size: .67rem;
    color: #f87171; white-space: nowrap;
}
.wk-clist-proof {
    font-family: var(--mono); font-size: .67rem;
    color: var(--teal-l); white-space: nowrap;
}

/* ═══════════════════════════════════════════════════════════
   COST CALCULATOR
═══════════════════════════════════════════════════════════ */
.wk-calc {
    background: var(--bg-2);
    border: 1px solid var(--border-2);
    border-radius: var(--r2);
    padding: 2.5rem;
    margin: var(--sp) 0;
    position: relative; overflow: hidden;
}
.wk-calc::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, var(--blue-d), var(--blue), var(--teal));
}
.wk-calc-title {
    font-family: var(--sans); font-size: 1.25rem; font-weight: 600;
    margin-bottom: .375rem;
}
.wk-calc-sub { color: var(--text-3); font-size: .875rem; margin-bottom: 2rem; }
.wk-calc-row { display: grid; grid-template-columns: 1fr auto auto; gap: 2rem; align-items: center; }
.wk-calc-slider-wrap { display: flex; flex-direction: column; gap: .625rem; }
.wk-calc-slider-label {
    font-family: var(--mono); font-size: .68rem; color: var(--text-3);
    letter-spacing: .08em; text-transform: uppercase;
    display: flex; justify-content: space-between;
}
.wk-calc-slider-label span { color: var(--blue-xl); font-size: .82rem; }
input[type=range].wk-slider {
    -webkit-appearance: none; appearance: none;
    width: 100%; height: 4px;
    background: var(--border-2); border-radius: 2px; outline: none;
    cursor: pointer;
}
input[type=range].wk-slider::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 16px; height: 16px; border-radius: 50%;
    background: var(--blue-l); border: 2px solid var(--bg-2);
    box-shadow: 0 0 8px rgba(96,165,250,.5);
}
.wk-calc-col {
    text-align: center; padding: 1.25rem 1.5rem;
    border-radius: var(--r); min-width: 140px;
}
.wk-calc-col-saas { background: rgba(239,68,68,.07); border: 1px solid rgba(239,68,68,.2); }
.wk-calc-col-wk   { background: rgba(59,130,246,.07); border: 1px solid rgba(59,130,246,.2); }
.wk-calc-col-val  {
    font-family: var(--serif); font-size: 1.75rem; font-weight: 300;
    line-height: 1; margin-bottom: .3rem;
}
.wk-calc-col-saas .wk-calc-col-val { color: #f87171; }
.wk-calc-col-wk   .wk-calc-col-val { color: var(--blue-xl); }
.wk-calc-col-lbl  { font-family: var(--mono); font-size: .6rem; color: var(--text-3); letter-spacing: .08em; text-transform: uppercase; }
.wk-calc-saving {
    margin-top: 1.5rem; padding: 1rem 1.5rem;
    background: rgba(34,197,94,.07); border: 1px solid rgba(34,197,94,.2);
    border-radius: var(--r); text-align: center;
}
.wk-calc-saving-num { font-family: var(--serif); font-size: 2.25rem; font-weight: 300; color: #4ade80; line-height: 1; }
.wk-calc-saving-lbl { font-family: var(--mono); font-size: .65rem; color: #86efac; letter-spacing: .08em; text-transform: uppercase; margin-top: .25rem; }

/* ═══════════════════════════════════════════════════════════
   PERSONA SPLIT — "Two ways to use Webkernel"
═══════════════════════════════════════════════════════════ */
.wk-persona {
    padding: var(--sp) 0;
    border-bottom: 1px solid var(--border);
}
.wk-persona-tabs {
    display: flex; gap: .5rem; margin-bottom: 2.5rem;
    padding: .25rem; background: var(--bg-2);
    border: 1px solid var(--border); border-radius: 12px;
    width: fit-content;
}
.wk-persona-tab {
    padding: .5rem 1.375rem;
    font-family: var(--mono); font-size: .72rem; letter-spacing: .08em;
    text-transform: uppercase; color: var(--text-3);
    border-radius: 8px; cursor: pointer;
    transition: color .18s, background .18s;
}
.wk-persona-tab.active { color: var(--text); background: var(--bg-3); box-shadow: var(--shadow-card); }
.wk-persona-pane { display: none; }
.wk-persona-pane.active { display: grid; grid-template-columns: repeat(2,1fr); gap: 1.5rem; }
.wk-persona-card {
    background: var(--glass); border: 1px solid var(--glass-border);
    border-radius: var(--r2); padding: 2rem;
    box-shadow: var(--shadow-card); backdrop-filter: blur(8px);
    display: flex; flex-direction: column; gap: 1rem;
    transition: border-color .2s, transform .2s;
}
.wk-persona-card:hover { border-color: rgba(59,130,246,.22); transform: translateY(-2px); }
.wk-persona-card-icon { font-size: 2rem; margin-bottom: .25rem; }
.wk-persona-card-tag { font-family: var(--mono); font-size: .62rem; letter-spacing: .1em; text-transform: uppercase; color: var(--blue); }
.wk-persona-card h3 { font-family: var(--sans); font-size: 1.1rem; font-weight: 600; line-height: 1.3; }
.wk-persona-card p { color: var(--text-3); font-size: .875rem; line-height: 1.7; flex: 1; }
.wk-persona-card-metric {
    display: inline-flex; align-items: center; gap: .5rem;
    font-family: var(--mono); font-size: .68rem; letter-spacing: .06em;
    color: var(--teal-l);
    background: var(--teal-bg); border: 1px solid rgba(20,184,166,.2);
    padding: .3rem .75rem; border-radius: 20px;
    width: fit-content;
}

/* ═══════════════════════════════════════════════════════════
   SYSTEM PANEL SHOWCASE
═══════════════════════════════════════════════════════════ */
.wk-panel-split {
    display: grid; grid-template-columns: 1fr 1.15fr;
    gap: 5.5rem; align-items: center;
}
.wk-panel-feature {
    display: flex; align-items: center; gap: .875rem;
    padding: .7rem 0; border-bottom: 1px solid var(--border);
}
.wk-panel-feature:last-child { border-bottom: none; }

.wk-panel-mockup {
    background: var(--glass);
    border: 1px solid var(--glass-border);
    border-radius: var(--r2); overflow: hidden;
    box-shadow: 0 40px 80px rgba(0,0,0,.65), 0 0 0 1px rgba(255,255,255,.06), var(--shadow-glow-blue);
    backdrop-filter: blur(12px);
    transform: perspective(1200px) rotateY(-4deg) rotateX(2deg);
    transition: transform .4s cubic-bezier(.23,1,.32,1);
}
.wk-panel-mockup:hover { transform: perspective(1200px) rotateY(-1deg) rotateX(0deg); }

/* ═══════════════════════════════════════════════════════════
   ARCHITECTURE DIAGRAM BLOCK
═══════════════════════════════════════════════════════════ */
.wk-arch {
    padding: var(--sp) 0;
    background: linear-gradient(180deg, var(--bg-1) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
}
.wk-arch-diagram {
    display: grid; grid-template-columns: 1fr 2px 1fr 2px 1fr;
    gap: 0; margin-top: 2.5rem; align-items: stretch;
}
.wk-arch-layer {
    background: var(--glass); border: 1px solid var(--glass-border);
    border-radius: var(--r2); padding: 1.75rem;
    backdrop-filter: blur(10px);
}
.wk-arch-divider {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: .3rem; padding: 1rem 0;
}
.wk-arch-divider-line {
    flex: 1; width: 1px; background: linear-gradient(180deg, transparent, var(--border-2), transparent);
}
.wk-arch-divider-arrow { color: var(--text-4); font-size: .75rem; }
.wk-arch-layer-title {
    font-family: var(--mono); font-size: .64rem; letter-spacing: .12em;
    text-transform: uppercase; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .625rem;
}
.wk-arch-layer-title-core    { color: var(--blue-l); }
.wk-arch-layer-title-modules { color: var(--teal-l); }
.wk-arch-layer-title-infra   { color: var(--amber-bright); }
.wk-arch-item {
    display: flex; align-items: center; gap: .625rem;
    padding: .45rem 0; border-bottom: 1px solid var(--border);
    font-size: .82rem; color: var(--text-2);
}
.wk-arch-item:last-child { border-bottom: none; }
.wk-arch-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

/* ═══════════════════════════════════════════════════════════
   QUOTE
═══════════════════════════════════════════════════════════ */
.wk-q {
    padding: var(--sp) 0; text-align: center;
    background: linear-gradient(180deg, var(--bg) 0%, var(--bg-1) 50%, var(--bg) 100%);
}
.wk-q blockquote {
    font-family: var(--serif); font-style: italic; font-weight: 300;
    font-size: clamp(1.6rem, 3.2vw, 2.85rem); line-height: 1.4;
    max-width: 860px; margin: 0 auto 1.875rem;
    color: rgba(238,240,248,.82);
}
.wk-q blockquote::before { content: '\201C'; color: var(--blue-l); }
.wk-q blockquote::after  { content: '\201D'; color: var(--blue-l); }
.wk-q-attr { font-family: var(--mono); font-size: .68rem; color: var(--text-3); letter-spacing: .1em; text-transform: uppercase; }
.wk-q-attr a { color: var(--blue-l); text-decoration: none; }

/* ═══════════════════════════════════════════════════════════
   RELEASES CAROUSEL
═══════════════════════════════════════════════════════════ */
.wk-tdot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: .45rem; }
.wk-tdot.stable  { background: var(--blue); box-shadow: 0 0 6px rgba(59,130,246,.5); }
.wk-tdot.lts     { background: var(--green); box-shadow: 0 0 6px rgba(34,197,94,.4); }
.wk-tdot.eol     { background: var(--border-2); }
.wk-tdot.planned { background: var(--blue-d); border: 1px solid var(--blue-l); }
.wk-tdot.roadmap { background: transparent; border: 1px dashed var(--text-4); }
.wk-tdot.current { background: var(--blue-xl); box-shadow: 0 0 10px var(--blue-l); }
.wk-tc { font-family: var(--serif); font-style: italic; font-size: 1.2rem; color: var(--blue-xl); margin-bottom: .2rem; }
.wk-tv { font-family: var(--mono); font-size: .67rem; color: var(--text-3); margin-bottom: .5rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.wk-thi { display: flex; flex-wrap: wrap; gap: .3rem; }
.wk-thi-t { font-size: .73rem; color: var(--text-3); background: var(--bg-2); border: 1px solid var(--border); padding: .12rem .55rem; border-radius: 4px; }
.wk-tteaser { font-size: .875rem; color: var(--text-3); line-height: 1.6; }

.wk-carousel-section { width: 100vw; margin-left: calc(-50vw + 50%); position: relative; }
.wk-carousel-wrap { overflow: hidden; padding: 0 0 1rem; }
.wk-carousel {
    display: flex; gap: 1.25rem;
    overflow-x: auto; scroll-behavior: smooth;
    scrollbar-width: none;
    padding: .5rem 2rem 1rem;
    cursor: grab; user-select: none;
}
.wk-carousel.dragging { cursor: grabbing; scroll-behavior: auto; }
.wk-carousel::-webkit-scrollbar { display: none; }
.wk-cs-card {
    flex-shrink: 0; width: clamp(220px, 28vw, 320px);
    background: var(--glass); border: 1px solid var(--glass-border);
    border-radius: var(--r2); padding: 1.5rem;
    box-shadow: var(--shadow-card); backdrop-filter: blur(8px);
    transition: border-color .18s, box-shadow .18s;
    pointer-events: none;
}
.wk-carousel:not(.dragging) .wk-cs-card { pointer-events: auto; }
.wk-cs-card:hover { border-color: var(--border-2); box-shadow: var(--shadow-elevated); }
.wk-cs-card.current {
    border-color: rgba(59,130,246,.42);
    background: rgba(29,78,216,.16);
    box-shadow: var(--shadow-elevated), 0 0 24px rgba(59,130,246,.12);
}
.wk-cs-top { display: flex; align-items: center; gap: .75rem; margin-bottom: .375rem; }
.wk-cs-hint {
    text-align: center; padding: .5rem 2rem 0;
    font-family: var(--mono); font-size: .62rem; color: var(--text-4);
    letter-spacing: .08em;
}

/* ═══════════════════════════════════════════════════════════
   CHANGELOG
═══════════════════════════════════════════════════════════ */
.wk-cl-sections { display: flex; flex-direction: column; gap: 1.25rem; }
.wk-cl-list { list-style: none; padding: .25rem 0; margin: 0; }
.wk-cl-list li {
    display: flex; gap: .75rem; padding: .55rem 0;
    border-bottom: 1px solid var(--border);
    font-size: .875rem; color: var(--text-2); line-height: 1.55;
}
.wk-cl-list li:last-child { border-bottom: none; }
.wk-cl-list li::before { content: '—'; color: var(--text-4); flex-shrink: 0; }

/* ═══════════════════════════════════════════════════════════
   SECTORS
═══════════════════════════════════════════════════════════ */
.wk-sector-pills { display: flex; flex-wrap: wrap; gap: .625rem; }
.wk-sector-pill {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .5rem 1.125rem;
    background: var(--glass); border: 1px solid var(--glass-border);
    border-radius: 20px; font-size: .84rem; color: var(--text-2);
    box-shadow: var(--shadow-card); backdrop-filter: blur(6px);
    transition: border-color .15s, background .15s, transform .15s; cursor: default;
}
.wk-sector-pill:hover { border-color: rgba(20,184,166,.32); color: var(--teal-l); transform: translateY(-1px); }

/* ═══════════════════════════════════════════════════════════
   COMMUNITY
═══════════════════════════════════════════════════════════ */
.wk-com-card {
    background: var(--bg-2); padding: 2.25rem;
    display: flex; flex-direction: column; gap: 1rem;
    transition: background .18s;
}
.wk-com-card:hover { background: var(--bg-3); }
.wk-com-card.featured { background: var(--bg-3); outline: 1px solid var(--border-2); outline-offset: -1px; }
.wk-com-card-icon { font-size: 1.75rem; }
.wk-com-card h3 { font-family: var(--sans); font-size: 1.05rem; font-weight: 600; line-height: 1.2; }
.wk-com-card h3 em { font-family: var(--serif); font-style: italic; font-weight: 300; color: var(--blue-xl); font-size: 1.1em; }
.wk-com-card p { color: var(--text-3); font-size: .875rem; line-height: 1.7; flex: 1; }
.wk-com-card-foot { margin-top: auto; padding-top: 1.25rem; }

/* ═══════════════════════════════════════════════════════════
   PRICING
═══════════════════════════════════════════════════════════ */
.wk-pc { background: var(--bg-2); padding: 2.5rem 2rem; display: flex; flex-direction: column; }
.wk-pc.featured { background: var(--bg-3); outline: 1px solid rgba(59,130,246,.32); outline-offset: -1px; }
.wk-pt { font-family: var(--mono); font-size: .67rem; letter-spacing: .12em; text-transform: uppercase; color: var(--blue-l); margin-bottom: 1.25rem; }
.wk-pa { font-family: var(--sans); font-size: 2rem; font-weight: 600; line-height: 1.1; margin-bottom: .3rem; }
.wk-pp { font-size: .8rem; color: var(--text-3); margin-bottom: 2rem; }
.wk-pf { list-style: none; flex: 1; margin-bottom: 2rem; padding: 0; }
.wk-pf li {
    display: flex; gap: .75rem; padding: .625rem 0;
    border-bottom: 1px solid var(--border);
    font-size: .875rem; color: var(--text-2);
}
.wk-pf li:last-child { border-bottom: none; }
.wk-pf li::before { content: '—'; color: var(--blue-d); flex-shrink: 0; }

/* ═══════════════════════════════════════════════════════════
   FINAL CTA
═══════════════════════════════════════════════════════════ */
.wk-cta {
    padding: calc(var(--sp) * 1.6) 0; text-align: center;
    position: relative; overflow: hidden;
}
.wk-cta-g {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    width: 1000px; height: 700px;
    background:
        radial-gradient(ellipse 640px 420px at 38% 50%, rgba(29,78,216,.16) 0%, transparent 60%),
        radial-gradient(ellipse 440px 320px at 68% 50%, rgba(20,184,166,.07) 0%, transparent 60%);
    pointer-events: none;
}
.wk-cta h2 {
    font-family: var(--sans); font-size: clamp(3rem, 6vw, 5.25rem);
    font-weight: 600; line-height: 1.04; letter-spacing: -.028em; margin-bottom: 1.375rem;
}
.wk-cta h2 em { font-family: var(--serif); font-style: italic; font-weight: 300; color: var(--blue-xl); font-size: 1.1em; }
.wk-cta-p { color: var(--text-2); font-size: 1.1rem; line-height: 1.82; max-width: 500px; margin: 0 auto 2.875rem; }
.wk-cta-a { display: flex; gap: .875rem; justify-content: center; flex-wrap: wrap; margin-bottom: 2.5rem; }
.wk-sig { font-family: var(--mono); font-size: .68rem; color: var(--text-3); letter-spacing: .05em; }
.wk-sig a { color: var(--blue-l); text-decoration: none; }

/* ═══════════════════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════════════════ */
.wk-footer {
    padding: 2rem 1.875rem; border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;
}
.wk-footer-brand { font-family: var(--sans); font-size: .9rem; font-weight: 600; color: var(--text-2); }
.wk-footer-brand span { color: var(--blue-l); }
.wk-footer-links { display: flex; gap: 1.5rem; flex-wrap: wrap; }
.wk-footer-links a { font-size: .78rem; color: var(--text-3); text-decoration: none; transition: color .15s; }
.wk-footer-links a:hover { color: var(--text); }
.wk-footer-copy { font-size: .7rem; color: var(--text-4); }

/* ═══════════════════════════════════════════════════════════
   UTILITIES
═══════════════════════════════════════════════════════════ */
.wk-sp { padding: var(--sp) 0; }
.wk-mb { margin-bottom: 3rem; }

.wk-reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease, transform .6s ease; }
.wk-reveal.visible { opacity: 1; transform: none; }
.wk-reveal-d1 { transition-delay: .1s; }
.wk-reveal-d2 { transition-delay: .2s; }
.wk-reveal-d3 { transition-delay: .3s; }

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════ */
@media (max-width: 1200px) {
    .wk-hero-layout { grid-template-columns: 1fr; }
    .wk-hero-panel { display: none; }
    .wk-stats-row { grid-template-columns: repeat(2,1fr); }
    .wk-arch-diagram { grid-template-columns: 1fr; gap: 1rem; }
    .wk-arch-divider { display: none; }
}
@media (max-width: 1024px) {
    .wk-card-grid-4 { grid-template-columns: repeat(2,1fr); }
    .wk-grid-4     { grid-template-columns: repeat(2,1fr); }
    .wk-grid-6     { grid-template-columns: repeat(3,1fr); }
    .wk-panel-split { grid-template-columns: 1fr; gap: 2.5rem; }
    .wk-odr { grid-template-columns: 1fr; }
    .wk-odr-stats { display: none; }
    .wk-calc-row { grid-template-columns: 1fr; }
    .wk-persona-pane.active { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    :root { --sp: 4rem; }
    .wk-nav-links { display: none; }
    .wk-grid-3, .wk-grid-2 { grid-template-columns: 1fr; }
    .wk-grid-6  { grid-template-columns: repeat(2,1fr); }
    .wk-compare { grid-template-columns: 1fr; gap: 2rem; }
    .wk-stats-row { grid-template-columns: repeat(2,1fr); }
    .wk-card-grid-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .wk-card-grid-4,
    .wk-card-grid-3 { grid-template-columns: 1fr; }
    .wk-grid-6      { grid-template-columns: 1fr 1fr; }
    .wk-hero-h1     { font-size: 2.75rem; }
    .wk-stats-row   { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<div class="wk-progress" id="wk-progress"></div>
<div class="wk-cursor" id="wk-cursor"></div>

{{-- NAV --}}
<nav class="wk-nav">
<a href="https://webkernelphp.com" class="wk-logo">
    <span class="wk-logo-text">Webkernel™</span>
    <span class="wk-logo-badge">Release {{ $release['version'] }}</span>
</a>
<ul class="wk-nav-links">
    <li><a href="#capabilities">Capabilities</a></li>
    <li><a href="#modules">Modules</a></li>
    <li><a href="#releases">Releases</a></li>
    <li><a href="#community">Community</a></li>
    <li><a href="#pricing">Pricing</a></li>
</ul>
<div class="wk-nav-right">
    <x-filament::button color="gray" size="sm" tag="a" href="https://github.com/webkernelphp/foundation" target="_blank">
    GitHub
    </x-filament::button>
    <x-filament::button color="primary" size="sm" tag="a" href="#contact">
    Reserve a slot
    </x-filament::button>
</div>
</nav>

{{-- BANNER --}}
<div class="wk-banner">
<span class="wk-banner-dot"></span>
<span class="wk-banner-t"><strong>Early Access Open</strong></span>
<span class="wk-banner-sep">·</span>
<span class="wk-banner-t">Save <strong>{{ $odrOffer['savings'] }}</strong></span>
<span class="wk-banner-sep">·</span>
<span class="wk-banner-t">Deadline <strong>{{ \Carbon\Carbon::parse($odrOffer['deadline'])->format('F j, Y') }}</strong></span>
<span class="wk-banner-sep">·</span>
<span class="wk-banner-t"><strong>{{ $odrOffer['slots'] - $odrOffer['taken'] }}</strong> of {{ $odrOffer['slots'] }} slots remaining</span>
<a href="#pricing" class="wk-banner-btn">Lock your price →</a>
</div>

{{-- ════════════════════════════════════════════════════════
     HERO — enterprise positioning, direct, specific
════════════════════════════════════════════════════════ --}}
<section class="wk-hero wk-s">

<div class="wk-orb wk-hero-orb-1"></div>
<div class="wk-orb wk-hero-orb-2"></div>
<div class="wk-orb wk-hero-orb-3"></div>
<div class="wk-hero-mesh"></div>
<div class="wk-hero-grid"></div>

<div class="wk-c wk-hero-inner">
    <div class="wk-hero-layout">

    <div>
        <div class="wk-ey a1">Webkernel™ Foundation — Release {{ $release['semver'] }}</div>

        {{-- Rewritten headline: enterprise-grade, specific, powerful --}}
        <h1 class="wk-h2 wk-hero-h1 a2">
        Stop renting your<br>
        <em>own infrastructure.</em>
        </h1>

        {{-- Rewritten sub: concrete, direct value prop --}}
        <p class="wk-hero-sub a3">
        Webkernel is the self-hosted application platform for organizations that
        cannot afford vendor lock-in. Deploy on your own servers, own every module
        permanently, and keep your data where it belongs — under your control.
        <strong style="color:var(--text);font-weight:500">No subscriptions. No lock-in. No surprises.</strong>
        </p>

        <div class="wk-hero-actions a4">
        <x-filament::button color="primary" size="lg" tag="a" href="#contact">
            Book a live walkthrough
        </x-filament::button>
        <x-filament::button color="gray" size="lg" tag="a" href="#capabilities" outlined>
            Explore {{ ucfirst($release['codename']) }}
        </x-filament::button>
        <x-filament::link color="gray" tag="a" href="https://github.com/webkernelphp/foundation" target="_blank">
            View on GitHub →
        </x-filament::link>
        </div>

        {{-- Trust signals: concrete, operational --}}
        <div class="wk-trust-strip a5">
        <div class="wk-trust-item">
            <span class="wk-trust-dot wk-trust-dot-green"></span>
            Self-hosted · your servers
        </div>
        <div class="wk-trust-item">
            <span class="wk-trust-dot wk-trust-dot-blue"></span>
            Buy once · no renewals
        </div>
        <div class="wk-trust-item">
            <span class="wk-trust-dot wk-trust-dot-amber"></span>
            Air-gap ready · offline mode
        </div>
        <div class="wk-trust-item">
            <span class="wk-trust-dot wk-trust-dot-green"></span>
            EPL-2.0 · open core
        </div>
        @if($release['commit'] !== 'unknown')
        <div class="wk-trust-item" style="margin-left:auto">
            <span style="font-family:var(--mono);font-size:.62rem;color:var(--text-4)">{{ $release['semver'] }} · {{ $release['commit'] }}</span>
        </div>
        @endif
        </div>
    </div>

    {{-- Right: System Panel mockup --}}
    <div class="wk-hero-panel a3">
        <div class="wk-pm-bar">
        <span class="wk-pm-dot wk-pm-dot-r"></span>
        <span class="wk-pm-dot wk-pm-dot-y"></span>
        <span class="wk-pm-dot wk-pm-dot-g"></span>
        <span class="wk-pm-title">Webkernel™ System Panel · /system</span>
        </div>
        <div class="wk-pm-body">
        <div class="wk-pm-sidebar">
            <div class="wk-pm-sidebar-logo">WK™ Panel</div>
            @foreach(['Dashboard','Server Info','Modules','Integrity','Maintenance','Credentials'] as $item)
            <div class="wk-pm-nav-item {{ $item === 'Dashboard' ? 'active' : '' }}">
                <span class="wk-pm-nav-dot"></span>
                {{ $item }}
            </div>
            @endforeach
        </div>
        <div class="wk-pm-content">
            <div class="wk-pm-stats-row">
            @foreach([['CPU','12%','info'],['RAM','412 MB','success'],['Disk','18 GB','warning'],['Uptime','14d','teal']] as [$k,$v,$c])
            <div class="wk-pm-stat wk-pm-stat-{{ $c }}">
                <div class="wk-pm-stat-val">{{ $v }}</div>
                <div class="wk-pm-stat-key">{{ $k }}</div>
            </div>
            @endforeach
            </div>
            <div class="wk-pm-integrity">
            <span class="wk-pm-integrity-dot"></span>
            <span class="wk-pm-integrity-text">SEAL VERIFIED · CoreManifest integrity OK</span>
            </div>
            <div class="wk-pm-chart-row">
            <div class="wk-pm-mini-chart">
                <div class="wk-pm-mini-chart-label">CPU LOAD</div>
                <div class="wk-pm-mini-chart-bar"><div class="wk-pm-mini-chart-fill" style="width:12%;background:var(--blue-l)"></div></div>
                <div class="wk-pm-mini-chart-val" style="color:var(--blue-l)">12%</div>
            </div>
            <div class="wk-pm-mini-chart">
                <div class="wk-pm-mini-chart-label">MEMORY</div>
                <div class="wk-pm-mini-chart-bar"><div class="wk-pm-mini-chart-fill" style="width:38%;background:#4ade80"></div></div>
                <div class="wk-pm-mini-chart-val" style="color:#4ade80">412 MB</div>
            </div>
            </div>
            <div class="wk-pm-mod-list">
            @foreach([['Invoicing','active'],['Kanban Boards','active'],['Calendars & ICS','update']] as [$mod,$status])
            <div class="wk-pm-mod-row">
                <span class="wk-pm-mod-name">{{ $mod }}</span>
                <span class="wk-pm-mod-status {{ $status }}">
                {{ $status === 'active' ? '● Active' : '↑ Update' }}
                </span>
            </div>
            @endforeach
            </div>
        </div>
        </div>
    </div>

    </div>
</div>

<div class="wk-scroll-hint">
    <div class="wk-scroll-arrow"></div>
    Scroll
</div>
</section>

{{-- ODR BLOCK --}}
<div class="wk-c wk-s">
<div class="wk-odr">
    <div>
    <div class="wk-odr-ey">Webkernel™ Early Access — Limited offer</div>
    <h3 class="wk-odr-h">Reserve now.<br><em>Save {{ $odrOffer['savings'] }} permanently.</em></h3>
    <p class="wk-odr-p">{{ $odrOffer['tagline'] }} This is the founding price — it will never be available again.</p>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        <x-filament::button color="primary" size="md" tag="a" href="#contact">Book a discovery call</x-filament::button>
        <x-filament::button color="gray" size="md" tag="a" href="#pricing" outlined>See what's included</x-filament::button>
    </div>
    </div>
    <div class="wk-odr-stats">
    <div>
        <div class="wk-odr-num">-{{ $odrOffer['discount'] }}%</div>
        <div class="wk-odr-lbl">Early access</div>
    </div>
    <div>
        <div class="wk-odr-lbl" style="margin-bottom:.5rem">Founding slots</div>
        <div class="wk-odr-dots">
        @for($i = 0; $i < $odrOffer['slots']; $i++)
            <span class="wk-odr-dot {{ $i < $odrOffer['taken'] ? 'taken' : '' }}"></span>
        @endfor
        </div>
        <div style="font-family:var(--mono);font-size:.62rem;color:var(--text-3);margin-top:.5rem">
        {{ $odrOffer['taken'] }} taken · {{ $odrOffer['slots'] - $odrOffer['taken'] }} left
        </div>
    </div>
    <div>
        <div class="wk-odr-lbl">Deadline</div>
        <div style="font-family:var(--serif);font-size:1.1rem;color:var(--blue-xl);margin-top:.25rem">
        {{ \Carbon\Carbon::parse($odrOffer['deadline'])->format('M j, Y') }}
        </div>
    </div>
    </div>
</div>
</div>

{{-- STATEMENT — rewritten copy --}}
<section class="wk-s wk-statement">
<div class="wk-c">
    <h2 class="wk-statement-h">Open-source core.<br><em>Permanent ownership.</em></h2>
    <p>Unlike SaaS platforms that charge monthly for software you'll never own, Webkernel's foundation is open-source at no cost. You purchase modules once and own them permanently. When you stop paying SaaS — you lose access. With Webkernel, there is nothing to stop paying.</p>
</div>
</section>

{{-- STATS ROW --}}
<div class="wk-c wk-s">
<div class="wk-stats-row">
    <div class="wk-stat-cell">
    <div class="wk-stat-num"><span class="wk-count-up" data-target="4600">4600</span><sup>+</sup></div>
    <div class="wk-stat-label">Built-in icons</div>
    </div>
    <div class="wk-stat-cell">
    <div class="wk-stat-num"><span class="wk-count-up" data-target="8">8</span></div>
    <div class="wk-stat-label">Platform capabilities</div>
    </div>
    <div class="wk-stat-cell">
    <div class="wk-stat-num">0</div>
    <div class="wk-stat-label">Monthly fees</div>
    </div>
    <div class="wk-stat-cell">
    <div class="wk-stat-num">100<sup>%</sup></div>
    <div class="wk-stat-label">Data residency</div>
    </div>
</div>
</div>

{{-- ════════════════════════════════════════════════════════
     PERSONA SECTION — "Two ways to use Webkernel" (NEW)
════════════════════════════════════════════════════════ --}}
<section class="wk-persona" id="use-cases">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Who uses Webkernel</div>
    <h2 class="wk-h2">Two paths.<br><em>One platform.</em></h2>
    <p class="wk-lead">Whether you're running critical infrastructure that cannot touch the cloud, or a growing business that wants to stop paying monthly for every tool — Webkernel is built for you.</p>
    </div>

    <div x-data="{ tab: 'enterprise' }">

    <x-filament::tabs label="Who uses Webkernel">
        <x-filament::tabs.item
            icon="heroicon-o-building-office-2"
            alpine-active="tab === 'enterprise'"
            x-on:click="tab = 'enterprise'"
        >
            Enterprise & Government
        </x-filament::tabs.item>
        <x-filament::tabs.item
            icon="heroicon-o-rocket-launch"
            alpine-active="tab === 'sme'"
            x-on:click="tab = 'sme'"
        >
            SMEs & Teams
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- Enterprise pane --}}
    <div x-show="tab === 'enterprise'" x-transition.opacity style="margin-top:2rem">
    <div class="wk-card-grid wk-card-grid-3">
    @foreach(array_values(array_filter($useCases, fn($u) => in_array($u['persona'], ['Enterprise','Healthcare','Government']))) as $uc)
    <div class="wk-persona-card">
        <div class="wk-persona-card-icon">{{ $uc['icon'] }}</div>
        <span class="wk-persona-card-tag">{{ $uc['persona'] }}</span>
        <h3>{{ $uc['title'] }}</h3>
        <p>{{ $uc['desc'] }}</p>
        <div class="wk-persona-card-metric">✓ {{ $uc['metric'] }}</div>
    </div>
    @endforeach
    </div>
    </div>

    {{-- SME pane --}}
    <div x-show="tab === 'sme'" x-transition.opacity x-cloak style="margin-top:2rem">
    <div class="wk-card-grid wk-card-grid-3">
    @foreach(array_values(array_filter($useCases, fn($u) => $u['persona'] === 'SME')) as $uc)
    <div class="wk-persona-card">
        <div class="wk-persona-card-icon">{{ $uc['icon'] }}</div>
        <span class="wk-persona-card-tag">{{ $uc['persona'] }}</span>
        <h3>{{ $uc['title'] }}</h3>
        <p>{{ $uc['desc'] }}</p>
        <div class="wk-persona-card-metric">✓ {{ $uc['metric'] }}</div>
    </div>
    @endforeach
    <div class="wk-persona-card">
        <div class="wk-persona-card-icon">💼</div>
        <span class="wk-persona-card-tag">SME</span>
        <h3>One platform for all your internal tools</h3>
        <p>CRM, HR, invoicing, and project management — all running on a single Webkernel instance. No integration overhead, no per-seat billing, no data scattered across 6 vendors.</p>
        <div class="wk-persona-card-metric">✓ 5 tools → 1 platform</div>
    </div>
    <div class="wk-persona-card">
        <div class="wk-persona-card-icon">🔐</div>
        <span class="wk-persona-card-tag">SME</span>
        <h3>Full control over who sees what</h3>
        <p>Role-based access control enforced at the backend — Owner, Admin, Developer, Viewer. Not just hidden in the UI. Every action is gated at the server level.</p>
        <div class="wk-persona-card-metric">✓ RBAC built-in</div>
    </div>
    </div>
    </div>

    </div>{{-- /x-data --}}
</div>
</section>

{{-- Remove now-unused custom persona tab CSS — tabs handled by Filament --}}

{{-- CAPABILITIES --}}
<section class="wk-s wk-sp" id="capabilities">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">What ships in {{ ucfirst($release['codename']) }}</div>
    <h2 class="wk-h2">Eight platform capabilities.<br><em>One coherent system.</em></h2>
    <p class="wk-lead">Each capability is a focused, sovereign system built into the kernel — not a plugin, not a dependency. They compose into a complete infrastructure layer your organization controls entirely.</p>
    </div>
    <div class="wk-card-grid wk-card-grid-4">
    @foreach($aptitudes as $apt)
    <div class="wk-fi-card visible">
        <div class="wk-fi-card-top">
        <div class="wk-apt-icon">
            <x-filament::icon :icon="$apt['icon']" class="h-5 w-5"/>
        </div>
        <x-filament::badge :color="$apt['color']">{{ $apt['badge'] }}</x-filament::badge>
        </div>
        <span class="wk-apt-tag">{{ $apt['tag'] }}</span>
        <div class="wk-apt-name">{{ $apt['name'] }}</div>
        <p class="wk-apt-desc">{{ $apt['desc'] }}</p>
    </div>
    @endforeach
    </div>
</div>
</section>

{{-- ARCHITECTURE DIAGRAM (NEW) --}}
<section class="wk-arch" id="architecture">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Platform architecture</div>
    <h2 class="wk-h2">Core. Modules. <em>Infrastructure.</em></h2>
    <p class="wk-lead">A clear separation of concerns. The open-source core is immutable and auditable. Modules are signed and versioned independently. Your infrastructure is never shared.</p>
    </div>
    <div class="wk-arch-diagram">
    {{-- Layer 1: Core --}}
    <div class="wk-arch-layer">
        <div class="wk-arch-layer-title wk-arch-layer-title-core">
        <span class="wk-arch-dot" style="background:var(--blue-l)"></span>
        Webkernel Core
        </div>
        @foreach(['WebernelAPI','Seal Enforcer','RBAC Engine','Heartbeat & Integrity','Auto-Update Engine','System Panel'] as $item)
        <div class="wk-arch-item">
            <span class="wk-arch-dot" style="background:var(--blue-bg2);border:1px solid var(--blue);"></span>
            {{ $item }}
        </div>
        @endforeach
        <div style="margin-top:.875rem;padding-top:.875rem;border-top:1px solid var(--border);font-family:var(--mono);font-size:.62rem;color:var(--text-4)">EPL-2.0 · Open source</div>
    </div>

    {{-- Divider --}}
    <div class="wk-arch-divider">
        <div class="wk-arch-divider-line"></div>
        <div class="wk-arch-divider-arrow">→</div>
        <div class="wk-arch-divider-line"></div>
    </div>

    {{-- Layer 2: Modules --}}
    <div class="wk-arch-layer">
        <div class="wk-arch-layer-title wk-arch-layer-title-modules">
        <span class="wk-arch-dot" style="background:var(--teal-l)"></span>
        First-party Modules
        </div>
        @foreach(['Invoicing','CRM','Kanban Boards','HR & Attendance','Website Builder','Calendars & ICS','Document Vault','Analytics Board'] as $item)
        <div class="wk-arch-item">
            <span class="wk-arch-dot" style="background:var(--teal-bg);border:1px solid var(--teal);"></span>
            {{ $item }}
        </div>
        @endforeach
        <div style="margin-top:.875rem;padding-top:.875rem;border-top:1px solid var(--border);font-family:var(--mono);font-size:.62rem;color:var(--text-4)">Signed · versioned · one-time purchase</div>
    </div>

    {{-- Divider --}}
    <div class="wk-arch-divider">
        <div class="wk-arch-divider-line"></div>
        <div class="wk-arch-divider-arrow">→</div>
        <div class="wk-arch-divider-line"></div>
    </div>

    {{-- Layer 3: Your Infrastructure --}}
    <div class="wk-arch-layer" style="border-color:rgba(251,191,36,.22)">
        <div class="wk-arch-layer-title wk-arch-layer-title-infra">
        <span class="wk-arch-dot" style="background:var(--amber-bright)"></span>
        Your Infrastructure
        </div>
        @foreach(['Your own server','Your country, your jurisdiction','Air-gapped (Sovereign tier)','Zero outbound connections','Your domain, your SSL','Your database, your backups'] as $item)
        <div class="wk-arch-item">
            <span class="wk-arch-dot" style="background:var(--amber-bg);border:1px solid var(--amber);"></span>
            {{ $item }}
        </div>
        @endforeach
        <div style="margin-top:.875rem;padding-top:.875rem;border-top:1px solid var(--border);font-family:var(--mono);font-size:.62rem;color:var(--text-4)">100% data residency · offline capable</div>
    </div>
    </div>
</div>
</section>

{{-- COMPARE — with costs --}}
<section class="wk-s wk-sp">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Why organizations switch</div>
    <h2 class="wk-h2">The real cost of<br><em>conventional SaaS.</em></h2>
    <p class="wk-lead">Monthly fees, vendor lock-in, and compliance exposure are not just inconveniences — they are compounding operational liabilities.</p>
    </div>
    <div class="wk-compare">
    <div>
        <div class="wk-compare-head bad">❌ Without Webkernel</div>
        <ul class="wk-clist bad">
        @foreach($problems as $p)
        <li><span class="ico-b">✕</span><span>{{ $p['label'] }}</span><span class="wk-clist-cost">{{ $p['cost'] }}</span></li>
        @endforeach
        </ul>
    </div>
    <div>
        <div class="wk-compare-head good">✓ With Webkernel</div>
        <ul class="wk-clist good">
        @foreach($solutions as $s)
        <li><span class="ico-g">✓</span><span>{{ $s['label'] }}</span><span class="wk-clist-proof">{{ $s['proof'] }}</span></li>
        @endforeach
        </ul>
    </div>
    </div>
</div>
</section>

{{-- COST CALCULATOR (NEW) --}}
@php
$calcScenarios = [
    [
        'label'    => 'Invoicing only',
        'tools'    => [['name'=>'Zoho Invoice','monthly'=>350],['name'=>'per-user seat','monthly'=>120]],
        'wk_price' => 3500,
        'wk_mod'   => 'Invoicing module',
    ],
    [
        'label'    => 'Invoicing + Scheduling',
        'tools'    => [['name'=>'Zoho Invoice','monthly'=>350],['name'=>'Calendly Business','monthly'=>480],['name'=>'per-user seats','monthly'=>200]],
        'wk_price' => 5400,
        'wk_mod'   => 'Invoicing + Calendars',
    ],
    [
        'label'    => 'Invoicing + Tasks + Calendar',
        'tools'    => [['name'=>'Zoho Invoice','monthly'=>350],['name'=>'Monday.com Basic','monthly'=>900],['name'=>'Calendly Business','monthly'=>480],['name'=>'per-user seats','monthly'=>320]],
        'wk_price' => 7900,
        'wk_mod'   => 'Invoicing + Kanban + Calendars',
    ],
    [
        'label'    => 'Full ops stack (4 tools)',
        'tools'    => [['name'=>'Zoho Invoice','monthly'=>350],['name'=>'HubSpot CRM Starter','monthly'=>1200],['name'=>'Monday.com Basic','monthly'=>900],['name'=>'Factorial HR','monthly'=>650],['name'=>'seats & integrations','monthly'=>400]],
        'wk_price' => 14300,
        'wk_mod'   => 'Invoicing + CRM + Kanban + HR',
    ],
    [
        'label'    => 'Full enterprise stack (6 tools)',
        'tools'    => [['name'=>'Zoho Invoice','monthly'=>350],['name'=>'HubSpot CRM Pro','monthly'=>2800],['name'=>'Monday.com Standard','monthly'=>1800],['name'=>'Factorial HR','monthly'=>650],['name'=>'Notion Business','monthly'=>480],['name'=>'DocuWare DMS','monthly'=>1100],['name'=>'seats, SSO & overages','monthly'=>700]],
        'wk_price' => 21500,
        'wk_mod'   => 'Invoicing + CRM + Kanban + HR + Vault + Analytics',
    ],
];
@endphp

<div class="wk-c wk-s" x-data="{
    idx: 1,
    scenarios: {{ json_encode($calcScenarios) }},
    years: 5,
    get scenario() { return this.scenarios[this.idx]; },
    get monthlyTotal() { return this.scenario.tools.reduce((s,t) => s + t.monthly, 0); },
    get yearlyTotal()  { return this.monthlyTotal * 12; },
    get saasTotal()    { return this.yearlyTotal * this.years; },
    get wkTotal()      { return this.scenario.wk_price + Math.round(this.scenario.wk_price * 0.25) * Math.max(0, this.years - 1); },
    get saving()       { return this.saasTotal - this.wkTotal; },
    fmt(n) { return n.toLocaleString('fr-MA') + ' MAD'; }
}">
<div class="wk-calc">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:2rem">
    <div>
        <div class="wk-calc-title">SaaS vs. Webkernel — cost over time</div>
        <p class="wk-calc-sub" style="margin-bottom:0">Real SaaS reference prices. Webkernel one-time purchase + optional annual updates at 25%.</p>
    </div>
    <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0">
        <span style="font-family:var(--mono);font-size:.65rem;color:var(--text-3);letter-spacing:.08em">YEARS:</span>
        @foreach([1,3,5] as $yr)
        <button
            x-on:click="years = {{ $yr }}"
            :style="years === {{ $yr }} ? 'background:var(--blue-bg2);color:var(--blue-xl);border-color:rgba(59,130,246,.35)' : ''"
            style="font-family:var(--mono);font-size:.7rem;padding:.3rem .75rem;border-radius:6px;border:1px solid var(--border-2);background:transparent;color:var(--text-3);cursor:pointer;transition:all .15s"
        >{{ $yr }}yr</button>
        @endforeach
    </div>
    </div>

    {{-- Scenario tabs via Filament --}}
    <x-filament::tabs label="Deployment scenarios" style="margin-bottom:1.75rem">
        @foreach($calcScenarios as $i => $sc)
        <x-filament::tabs.item
            :alpine-active="'idx === ' . $i"
            x-on:click="idx = {{ $i }}"
        >
            {{ $sc['label'] }}
        </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{-- SaaS breakdown table --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

    <div>
        <div style="font-family:var(--mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#f87171;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem">
        <span style="width:6px;height:6px;border-radius:50%;background:#f87171;flex-shrink:0"></span>
        Equivalent SaaS tools
        </div>
        <div style="display:flex;flex-direction:column;gap:.3rem">
        <template x-for="tool in scenario.tools" :key="tool.name">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:rgba(239,68,68,.04);border:1px solid rgba(239,68,68,.1);border-radius:6px;font-size:.82rem">
            <span style="color:var(--text-2)" x-text="tool.name"></span>
            <span style="font-family:var(--mono);font-size:.72rem;color:#f87171" x-text="tool.monthly.toLocaleString('fr-MA') + ' MAD/mo'"></span>
            </div>
        </template>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .75rem;margin-top:.25rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.22);border-radius:6px">
            <span style="font-family:var(--mono);font-size:.68rem;color:#fca5a5;letter-spacing:.06em">TOTAL / MONTH</span>
            <span style="font-family:var(--serif);font-size:1.15rem;color:#f87171" x-text="fmt(monthlyTotal)"></span>
        </div>
        </div>
    </div>

    <div>
        <div style="font-family:var(--mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:var(--blue-l);margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem">
        <span style="width:6px;height:6px;border-radius:50%;background:var(--blue-l);flex-shrink:0"></span>
        Webkernel equivalent
        </div>
        <div style="display:flex;flex-direction:column;gap:.3rem">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:var(--blue-bg);border:1px solid rgba(59,130,246,.15);border-radius:6px;font-size:.82rem">
            <span style="color:var(--text-2)" x-text="scenario.wk_mod"></span>
            <span style="font-family:var(--mono);font-size:.72rem;color:var(--blue-xl)" x-text="fmt(scenario.wk_price) + ' once'"></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:rgba(255,255,255,.025);border:1px solid var(--border);border-radius:6px;font-size:.82rem">
            <span style="color:var(--text-3)">Optional annual update</span>
            <span style="font-family:var(--mono);font-size:.72rem;color:var(--text-3)" x-text="'≤ ' + fmt(Math.round(scenario.wk_price * 0.25)) + '/yr'"></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:rgba(255,255,255,.025);border:1px solid var(--border);border-radius:6px;font-size:.82rem">
            <span style="color:var(--text-3)">Self-hosted server (VPS)</span>
            <span style="font-family:var(--mono);font-size:.72rem;color:var(--text-3)">~300 MAD/mo</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .75rem;margin-top:.25rem;background:var(--blue-bg2);border:1px solid rgba(59,130,246,.28);border-radius:6px">
            <span style="font-family:var(--mono);font-size:.68rem;color:var(--blue-xl);letter-spacing:.06em">TOTAL OVER <span x-text="years"></span> YR</span>
            <span style="font-family:var(--serif);font-size:1.15rem;color:var(--blue-xl)" x-text="fmt(wkTotal + 300*12*years)"></span>
        </div>
        </div>
    </div>

    </div>

    {{-- Big saving --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1px;background:var(--border);border-radius:var(--r2);overflow:hidden;border:1px solid var(--border)">
    <div style="background:rgba(239,68,68,.06);padding:1.5rem 2rem;text-align:center">
        <div style="font-family:var(--serif);font-size:2rem;font-weight:300;color:#f87171;line-height:1" x-text="fmt(saasTotal)"></div>
        <div style="font-family:var(--mono);font-size:.6rem;color:var(--text-4);letter-spacing:.08em;text-transform:uppercase;margin-top:.4rem">SaaS over <span x-text="years"></span> years</div>
    </div>
    <div style="background:var(--blue-bg);padding:1.5rem 2rem;text-align:center">
        <div style="font-family:var(--serif);font-size:2rem;font-weight:300;color:var(--blue-xl);line-height:1" x-text="fmt(wkTotal + 300*12*years)"></div>
        <div style="font-family:var(--mono);font-size:.6rem;color:var(--text-4);letter-spacing:.08em;text-transform:uppercase;margin-top:.4rem">Webkernel over <span x-text="years"></span> years</div>
    </div>
    <div style="background:rgba(34,197,94,.07);padding:1.5rem 2rem;text-align:center;border-left:2px solid rgba(34,197,94,.3)">
        <div style="font-family:var(--serif);font-size:2rem;font-weight:300;color:#4ade80;line-height:1" x-text="fmt(Math.max(0, saasTotal - (wkTotal + 300*12*years)))"></div>
        <div style="font-family:var(--mono);font-size:.6rem;color:#86efac;letter-spacing:.08em;text-transform:uppercase;margin-top:.4rem">Estimated saving</div>
    </div>
    </div>

    <div style="margin-top:1rem;font-family:var(--mono);font-size:.6rem;color:var(--text-4);line-height:1.6">
    SaaS prices are indicative reference prices in MAD equivalent at time of writing. Webkernel prices are exact. Annual update at 25% of original price is optional and contractually capped. Server cost assumes a standard VPS at ~300 MAD/mo.
    </div>
</div>
</div>

{{-- MODULES --}}
<section class="wk-s wk-sp" id="modules">
<div class="wk-c">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1.5rem;margin-bottom:3rem">
    <div>
        <div class="wk-ey">First-party modules</div>
        <h2 class="wk-h2" style="margin-bottom:0">Buy once.<br><em>Own forever.</em></h2>
    </div>
    <p style="color:var(--text-3);font-size:.875rem;max-width:320px;line-height:1.65;text-align:right">Cryptographically signed packages. Compatibility declared explicitly per Webkernel release. Annual updates optional — never more than 25% of original price.</p>
    </div>
    <div class="wk-card-grid wk-card-grid-4">
    @foreach($modules as $m)
    <div class="wk-fi-card visible" style="display:flex;flex-direction:column">
        <span class="wk-mod-tag">{{ $m['tag'] }}</span>
        <div class="wk-mod-name">{{ $m['name'] }}</div>
        <p class="wk-mod-desc">{{ $m['desc'] }}</p>
        <div class="wk-mod-foot">
        <div class="wk-mod-price">From <strong>{{ $m['price'] }} MAD</strong></div>
        <div class="wk-mod-tags">
            @foreach($m['compatible'] as $ct)
            <span class="wk-mod-ct {{ $ct === 'upcoming' ? 'upcoming' : '' }}">
                {{ $ct === 'upcoming' ? 'Coming' : $ct }}
            </span>
            @endforeach
        </div>
        </div>
    </div>
    @endforeach
    </div>
</div>
</section>

{{-- SYSTEM PANEL SHOWCASE --}}
<section class="wk-s wk-sp" style="background:linear-gradient(180deg,var(--bg) 0%,var(--bg-1) 100%)">
<div class="wk-c">
    <div class="wk-panel-split">
    <div>
        <div class="wk-ey">System Panel</div>
        <h2 class="wk-h2">Full visibility.<br><em>One interface.</em></h2>
        <p style="color:var(--text-2);font-size:1rem;line-height:1.82;margin-bottom:.75rem">
        A production-grade Filament 5 administration panel built into the kernel.
        Live system metrics, module management, maintenance controls, and
        role-based access — all from the same interface that ships with every deployment.
        </p>
        <p style="color:var(--text-3);font-size:.875rem;line-height:1.75;margin-bottom:2rem">
        Not a demo panel bolted on. Not a third-party plugin. Every metric, every control, every integrity signal is first-class — built by the same team that built the kernel.
        </p>
        <div style="display:flex;flex-direction:column;gap:.875rem">
        @foreach([
            ['heroicon-o-chart-bar',    'Live CPU, memory, disk, and FPM metrics — no external APM needed'],
            ['heroicon-o-puzzle-piece', 'Activate, deactivate, and update modules without touching the codebase'],
            ['heroicon-o-lock-closed',  'RBAC — every action gated at the backend, not just the UI layer'],
            ['heroicon-o-wrench',       'Cache flush, OPCache reset, manifest refresh — one click in production'],
        ] as [$icon, $label])
        <div class="wk-panel-feature">
            <div class="wk-apt-icon" style="flex-shrink:0">
            <x-filament::icon :icon="$icon" class="h-4 w-4"/>
            </div>
            <span style="font-size:.9rem;color:var(--text-2)">{{ $label }}</span>
        </div>
        @endforeach
        </div>
        <div style="margin-top:2.25rem">
        <x-filament::button color="primary" size="md" tag="a" href="#contact">
            Book a live walkthrough →
        </x-filament::button>
        </div>
    </div>

    <div class="wk-panel-mockup">
        <div class="wk-pm-bar">
        <span class="wk-pm-dot wk-pm-dot-r"></span>
        <span class="wk-pm-dot wk-pm-dot-y"></span>
        <span class="wk-pm-dot wk-pm-dot-g"></span>
        <span class="wk-pm-title">System Panel · Dashboard</span>
        </div>
        <div class="wk-pm-body">
        <div class="wk-pm-sidebar">
            <div class="wk-pm-sidebar-logo">WK™ v{{ $release['version'] }}</div>
            @foreach(['Dashboard','Server Info','Modules','Integrity','Maintenance','Credentials','Audit Log'] as $item)
            <div class="wk-pm-nav-item {{ $item === 'Dashboard' ? 'active' : '' }}">
                <span class="wk-pm-nav-dot"></span>
                {{ $item }}
            </div>
            @endforeach
        </div>
        <div class="wk-pm-content">
            <div class="wk-pm-stats-row">
            @foreach([['CPU','12%','info'],['RAM','412 MB','success'],['Disk','18 GB','warning'],['Uptime','14d','teal']] as [$k,$v,$c])
            <div class="wk-pm-stat wk-pm-stat-{{ $c }}">
                <div class="wk-pm-stat-val">{{ $v }}</div>
                <div class="wk-pm-stat-key">{{ $k }}</div>
            </div>
            @endforeach
            </div>
            <div class="wk-pm-integrity">
            <span class="wk-pm-integrity-dot"></span>
            <span class="wk-pm-integrity-text">SEAL ENFORCER · All 847 files verified · Waterfall 1.3.32</span>
            </div>
            <div class="wk-pm-chart-row">
            <div class="wk-pm-mini-chart">
                <div class="wk-pm-mini-chart-label">CPU 7-DAY</div>
                <div class="wk-pm-mini-chart-bar"><div class="wk-pm-mini-chart-fill" style="width:12%;background:var(--blue-l)"></div></div>
                <div class="wk-pm-mini-chart-val" style="color:var(--blue-l)">avg 12%</div>
            </div>
            <div class="wk-pm-mini-chart">
                <div class="wk-pm-mini-chart-label">DISK USED</div>
                <div class="wk-pm-mini-chart-bar"><div class="wk-pm-mini-chart-fill" style="width:45%;background:var(--amber-bright)"></div></div>
                <div class="wk-pm-mini-chart-val" style="color:var(--amber-bright)">18 / 40 GB</div>
            </div>
            </div>
            <div class="wk-pm-mod-list">
            @foreach([['Invoicing','active'],['Kanban Boards','active'],['Calendars & ICS','active'],['Website Builder','update']] as [$mod,$status])
            <div class="wk-pm-mod-row">
                <span class="wk-pm-mod-name">{{ $mod }}</span>
                <span class="wk-pm-mod-status {{ $status }}">
                {{ $status === 'active' ? '● Active' : '↑ Update available' }}
                </span>
            </div>
            @endforeach
            </div>
        </div>
        </div>
    </div>
    </div>
</div>
</section>

{{-- QUOTE --}}
<section class="wk-s wk-q">
<div class="wk-c">
    <blockquote>Software should be a reliable workforce under your direct command — not a monthly subscription that holds your operations hostage.</blockquote>
    <div class="wk-q-attr">Yassine El Moumen · Founder & Architect · <a href="https://www.numerimondes.com" target="_blank">Numerimondes</a> · Casablanca, Morocco</div>
</div>
</section>

{{-- RELEASES TIMELINE --}}
<section class="wk-s wk-sp" id="releases">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Release history & roadmap</div>
    <h2 class="wk-h2">Every release is a <em>named event.</em></h2>
    <p class="wk-lead">Webkernel releases carry codenames, not just version numbers. Each represents a capability milestone. Modules declare compatibility explicitly.</p>
    </div>
    <div style="margin-bottom:2rem">
    <x-filament::callout icon="heroicon-o-sparkles" color="primary">
        <x-slot name="heading">Now available: Webkernel™ {{ $release['semver'] }} "{{ ucfirst($release['codename']) }}"</x-slot>
        Released {{ \Carbon\Carbon::parse($release['released_at'])->format('F j, Y') }} · Stable channel · Early access pricing until {{ \Carbon\Carbon::parse($odrOffer['deadline'])->format('F j, Y') }}
    </x-filament::callout>
    </div>

    <div class="wk-carousel-section">
    <div class="wk-carousel-wrap">
    <div class="wk-carousel" id="wk-carousel">
        @php
        $allReleases = array_merge(
        array_map(fn($r) => array_merge($r, ['type' => 'past']), $previousReleases),
        [[
            'version'  => $release['semver'],
            'codename' => $release['codename'],
            'date'     => $release['released_at'],
            'status'   => 'current',
            'type'     => 'current',
            'highlights' => ['Current release · Early access open'],
        ]],
        array_map(fn($r) => array_merge($r, ['type' => 'upcoming']), $upcomingReleases)
        );
        $carousel = array_merge($allReleases, $allReleases);
        @endphp
        @foreach($carousel as $idx => $rel)
        <div class="wk-cs-card {{ $rel['type'] === 'current' ? 'current' : '' }}">
        <div class="wk-cs-top">
            <span class="wk-tdot {{ $rel['status'] }}" style="margin-top:0;flex-shrink:0"></span>
            <span class="wk-tc" style="font-size:1.1rem">{{ ucfirst($rel['codename']) }}</span>
        </div>
        <div class="wk-tv" style="margin-bottom:.75rem">
            {{ $rel['version'] ?? '' }}
            @if(isset($rel['date']))
            · {{ \Carbon\Carbon::parse($rel['date'])->format('M Y') }}
            @elseif(isset($rel['eta']))
            · {{ $rel['eta'] }}
            @endif
        </div>
        @if(isset($rel['highlights']))
            <div class="wk-thi">
            @foreach($rel['highlights'] as $hi)
                <span class="wk-thi-t">{{ $hi }}</span>
            @endforeach
            </div>
        @elseif(isset($rel['teaser']))
            <p class="wk-tteaser">{{ $rel['teaser'] }}</p>
        @endif
        </div>
        @endforeach
    </div>
    </div>
    <div class="wk-cs-hint">Drag to explore · scroll right for upcoming releases</div>
    </div>

    <div style="margin-top:2rem">
    <x-filament::callout icon="heroicon-o-light-bulb" color="warning">
        <x-slot name="description">
            <span class="fi-callout-heading">Have a suggestion? Shape the roadmap.</span>
            The most-requested capabilities ship first.
            <x-filament::link href="#community" color="warning">Submit a suggestion →</x-filament::link>
        </x-slot>
    </x-filament::callout>
    </div>
</div>
</section>

{{-- CHANGELOG --}}
<section class="wk-s wk-sp">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Webkernel™ {{ $release['semver'] }}</div>
    <h2 class="wk-h2">Full <em>changelog.</em></h2>
    </div>
    <div class="wk-cl-sections">
    @php
    $metaMap = [
        'added'    => ['success','Added','New features and enhancements'],
        'changed'  => ['info','Changed','Updates and modifications'],
        'security' => ['danger','Security','Security-related improvements'],
        'fixed'    => ['warning','Fixed','Bug fixes and corrections'],
    ];
    @endphp
    @foreach($metaMap as $key => [$badgeType,$label,$desc])
    <div id="cl-{{ $key }}" class="wk-cl-section">
        <x-filament::section compact aside>
            <x-slot name="heading">
                <span>{{ $label }}</span>
                <x-filament::badge color="{{ $badgeType }}" style="margin-left:.75rem;">
                    {{ count($changelog[$key]) }}
                </x-filament::badge>
            </x-slot>
            <x-slot name="description">{{ $desc }}</x-slot>
            <ul class="wk-cl-list">
                @foreach($changelog[$key] as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>
    @endforeach
    </div>
</div>
</section>

{{-- SECTORS --}}
<section class="wk-s wk-sp">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Industries served</div>
    <h2 class="wk-h2">Built for organizations<br>that cannot afford <em>dependencies.</em></h2>
    <p class="wk-lead">From regulated finance and healthcare to government agencies and NGOs — anywhere that data sovereignty, operational continuity, and budget predictability are non-negotiable.</p>
    </div>
    <div class="wk-sector-pills">
    @foreach($sectors as $sec)
    <div class="wk-sector-pill">
        <span>{{ $sec['icon'] }}</span>
        <span>{{ $sec['label'] }}</span>
    </div>
    @endforeach
    </div>
</div>
</section>

{{-- COMMUNITY --}}
<section class="wk-s wk-sp" id="community">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Join the conversation</div>
    <h2 class="wk-h2">Direct access to<br>the <em>people who built it.</em></h2>
    </div>
    <div class="wk-grid wk-grid-3">
    <div class="wk-com-card" style="background:var(--bg-2)">
        <div class="wk-com-card-icon">💬</div>
        <h3>Ask a <em>technical question</em></h3>
        <p>Architecture questions, integration scenarios, deployment constraints — ask openly. Every question gets a direct technical answer from the Webkernel team. No sales funnel.</p>
        <div class="wk-com-card-foot">
        <x-filament::button color="gray" tag="a" href="https://github.com/webkernelphp/foundation/discussions" target="_blank" outlined>
            GitHub Discussions
        </x-filament::button>
        </div>
    </div>
    <div class="wk-com-card featured">
        <div class="wk-com-card-icon">📞</div>
        <h3>Book a <em>live walkthrough</em></h3>
        <p>30 minutes with the founder. Walk through the platform live, review your specific architecture requirements, and get a straight answer on fit — without pressure or follow-up sequences.</p>
        <div class="wk-com-card-foot">
        <x-filament::button color="primary" tag="a" href="https://www.linkedin.com/in/elmoumenyassine/" target="_blank">
            Book on LinkedIn →
        </x-filament::button>
        </div>
    </div>
    <div class="wk-com-card" style="background:var(--bg-2)">
        <div class="wk-com-card-icon">🗺️</div>
        <h3>Shape the <em>roadmap</em></h3>
        <p>Have a module idea, a sector-specific requirement, or an integration use case? Submit it with context. The most-requested capabilities ship first in upcoming releases.</p>
        <div class="wk-com-card-foot">
        <x-filament::button color="gray" tag="a" href="https://github.com/webkernelphp/foundation/issues" target="_blank" outlined>
            Submit a suggestion
        </x-filament::button>
        </div>
    </div>
    </div>
</div>
</section>

{{-- PRICING --}}
<section class="wk-s wk-sp" id="pricing">
<div class="wk-c">
    <div class="wk-sh">
    <div class="wk-ey">Licensing</div>
    <h2 class="wk-h2">Start free. Scale<br>without <em>limits.</em></h2>
    <p class="wk-lead">The open-source core is always free. You purchase modules once and own them permanently. Annual updates are optional, capped contractually at 25% of original price.</p>
    </div>
    <div class="wk-grid wk-grid-3">
    @foreach($pricing as $plan)
    <div class="wk-pc {{ $plan['featured'] ? 'featured' : '' }}">
        <div class="wk-pt">{{ $plan['tier'] }}</div>
        <div class="wk-pa">{{ $plan['price'] }}</div>
        <div class="wk-pp">{{ $plan['period'] }}</div>
        <ul class="wk-pf">
        @foreach($plan['features'] as $f)
        <li>{{ $f }}</li>
        @endforeach
        </ul>
        @if($plan['featured'])
        <x-filament::button color="primary" size="md" tag="a" :href="$plan['href']" class="w-full justify-center">
            {{ $plan['cta'] }}
        </x-filament::button>
        @else
        <x-filament::button color="gray" size="md" tag="a" :href="$plan['href']" outlined class="w-full justify-center">
            {{ $plan['cta'] }}
        </x-filament::button>
        @endif
    </div>
    @endforeach
    </div>
    <div style="margin-top:1.75rem">
    <x-filament::callout icon="heroicon-o-information-circle" color="info">
        <x-slot name="heading">Founding client offer — expires {{ \Carbon\Carbon::parse($odrOffer['deadline'])->format('F j, Y') }}</x-slot>
        The first {{ $odrOffer['slots'] }} clients to deploy Webkernel {{ ucfirst($release['codename']) }} receive {{ $odrOffer['savings'] }} off their total module purchase. {{ $odrOffer['taken'] }} slot taken · {{ $odrOffer['slots'] - $odrOffer['taken'] }} remaining.
    </x-filament::callout>
    </div>
</div>
</section>

{{-- FINAL CTA — rewritten --}}
<section class="wk-cta" id="contact">
<div class="wk-cta-g"></div>
<div class="wk-c" style="position:relative">
    <h2>Stop renting.<br>Start <em>owning.</em></h2>
    <p class="wk-cta-p">Book a 30-minute call. We walk through the platform live, review your infrastructure requirements, and tell you directly if Webkernel is the right fit. No pitch deck. No follow-up sequence.</p>
    <div class="wk-cta-a">
    <x-filament::button color="primary" size="xl" tag="a" href="https://www.linkedin.com/in/elmoumenyassine/" target="_blank">
        Book a live walkthrough →
    </x-filament::button>
    <x-filament::button color="gray" size="xl" tag="a" href="tel:00212620990692" outlined>
        +212 6 2099 0692
    </x-filament::button>
    </div>
    <div class="wk-sig">
    Yassine El Moumen · Founder & Architect ·
    <a href="https://webkernelphp.com" target="_blank">webkernelphp.com</a> ·
    <a href="https://www.numerimondes.com" target="_blank">numerimondes.com</a> ·
    Casablanca, Morocco
    </div>
</div>
</section>

{{-- FOOTER --}}
<footer class="wk-footer">
<div class="wk-footer-brand">Webkernel<span>™</span> · Numerimondes</div>
<div class="wk-footer-links">
    <a href="https://webkernelphp.com" target="_blank">Documentation</a>
    <a href="https://github.com/webkernelphp/foundation" target="_blank">GitHub</a>
    <a href="https://www.linkedin.com/in/elmoumenyassine/" target="_blank">LinkedIn</a>
    <a href="https://www.numerimondes.com" target="_blank">Numerimondes</a>
    <a href="#community">Community</a>
    <a href="#contact">Contact</a>
</div>
<div class="wk-footer-copy">
    © {{ date('Y') }} Numerimondes · EPL-2.0 Open Core · Webkernel™ {{ $release['semver'] }}
</div>
</footer>

@filamentScripts
</body>
</html>
