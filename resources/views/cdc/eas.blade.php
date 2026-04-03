{{--
    CDC VISUEL — EASBridge www.easbridge.com
    Prototype interactif — toutes pages mockées
    Module WebKernel unique : easbridge-site
    Accord n° 19CQRCFDP — Numerimondes × EASBridge
    ─────────────────────────────────────────────────
    Fonts : Syne (display) + Plus Jakarta Sans (body)
    Palette : vert vivid #22c55e → #16a34a, bleu royal #1E3A8A, blanc #FFFFFF
    Usage : @include ou route dédiée dans WebKernel

    Alpine.js est chargé directement ici pour que le fichier soit autonome.
    Si le layout parent WebKernel charge déjà Alpine.js, supprimer la
    balise <script> ci-dessous pour éviter un double chargement.
--}}

{{-- ── Alpine.js — chargé ici pour autonomie du prototype ──────
     Si le layout parent WebKernel inclut déjà Alpine, retirer ce bloc.
     Le defer garantit que Alpine s'initialise après le DOM complet.
──────────────────────────────────────────────────────────────── --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&display=swap" rel="stylesheet">

<style>
/* ── Alpine : init guard ──────────────────────────────────────── */
[x-cloak] { display: none !important; }
.eb-page-panel { display: none; }
.eb-page-panel.active { display: block; }

/* ── Reset & Root ─────────────────────────────────────────────── */
.eb-proto *, .eb-proto *::before, .eb-proto *::after { box-sizing: border-box; margin: 0; padding: 0; }
.eb-proto { font-family: 'Plus Jakarta Sans', sans-serif; background: #fff; color: #0f172a; line-height: 1.6; }
.eb-proto img { max-width: 100%; display: block; }
.eb-proto a { text-decoration: none; color: inherit; }

/* ── Brand tokens ─────────────────────────────────────────────── */
.eb-proto {
    --eb-green:       #22c55e;
    --eb-green-dark:  #16a34a;
    --eb-green-deep:  #14532d;
    --eb-green-pale:  #f0fdf4;
    --eb-blue:        #1e3a8a;
    --eb-blue-mid:    #1d4ed8;
    --eb-blue-light:  #eff6ff;
    --eb-navy:        #0f172a;
    --eb-slate:       #1e293b;
    --eb-muted:       #64748b;
    --eb-border:      #e2e8f0;
    --eb-white:       #ffffff;
    --eb-surface:     #f8fafc;
    --eb-radius:      .5rem;
    --eb-radius-lg:   1rem;
    --eb-shadow:      0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --eb-shadow-lg:   0 8px 32px rgba(0,0,0,.12);
}

/* ── CDC Shell ────────────────────────────────────────────────── */
.eb-cdc-shell {
    display: flex;
    min-height: 100vh;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ── CDC Sidebar nav ──────────────────────────────────────────── */
.eb-cdc-nav {
    width: 260px;
    flex-shrink: 0;
    background: var(--eb-navy);
    color: #fff;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
}
.eb-cdc-nav-logo {
    padding: 1.5rem 1.25rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.eb-cdc-nav-logo-mark {
    display: flex; align-items: center; gap: .6rem;
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem; font-weight: 700;
    color: #fff;
}
.eb-logo-square {
    width: 32px; height: 32px;
    background: var(--eb-green);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 800; color: #fff;
    font-family: 'Syne', sans-serif;
    flex-shrink: 0;
}
.eb-cdc-nav-meta {
    font-size: .68rem; color: rgba(255,255,255,.4);
    padding: .5rem 1.25rem .75rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.eb-cdc-nav-section {
    padding: .75rem 1.25rem .25rem;
    font-size: .63rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: rgba(255,255,255,.3);
    font-weight: 600;
}
.eb-cdc-nav-item {
    display: flex; align-items: center; gap: .6rem;
    padding: .55rem 1.25rem;
    font-size: .82rem; color: rgba(255,255,255,.65);
    cursor: pointer;
    transition: all .15s;
    border-left: 2px solid transparent;
}
.eb-cdc-nav-item:hover, .eb-cdc-nav-item.active {
    color: #fff;
    background: rgba(255,255,255,.05);
    border-left-color: var(--eb-green);
}
.eb-cdc-nav-item .dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    flex-shrink: 0;
    transition: background .15s;
}
.eb-cdc-nav-item.active .dot, .eb-cdc-nav-item:hover .dot { background: var(--eb-green); }
.eb-cdc-nav-badge {
    margin-left: auto;
    font-size: .6rem; padding: .1rem .4rem;
    background: var(--eb-green-dark); color: #fff;
    border-radius: 9999px; font-weight: 600;
}

/* ── CDC Main ─────────────────────────────────────────────────── */
.eb-cdc-main { flex: 1; overflow-x: hidden; }

/* ── CDC Page Label Banner ────────────────────────────────────── */
.eb-page-label {
    background: var(--eb-navy);
    color: rgba(255,255,255,.5);
    font-size: .7rem;
    font-family: 'Plus Jakarta Sans', monospace;
    padding: .4rem 1.5rem;
    display: flex; align-items: center; gap: .75rem;
    letter-spacing: .04em;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.eb-page-label strong { color: var(--eb-green); }
.eb-page-label-route {
    background: rgba(255,255,255,.08);
    border-radius: 4px; padding: .1rem .5rem;
    font-size: .68rem; color: rgba(255,255,255,.6);
}

/* ══════════════════════════════════════════════════════════════
   SHARED COMPONENTS
══════════════════════════════════════════════════════════════ */

/* NAV */
.eb-nav {
    position: sticky; top: 0; z-index: 50;
    background: rgba(255,255,255,.97);
    backdrop-filter: blur(8px);
    border-bottom: 1px solid var(--eb-border);
    padding: 0 2rem;
    display: flex; align-items: center; justify-content: space-between;
    height: 64px;
}
.eb-nav-logo { display: flex; align-items: center; gap: .65rem; }
.eb-nav-logo-text { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--eb-navy); }
.eb-nav-links { display: flex; align-items: center; gap: .25rem; }
.eb-nav-link {
    padding: .45rem .85rem;
    font-size: .85rem; font-weight: 500;
    color: var(--eb-slate);
    border-radius: var(--eb-radius);
    transition: all .15s;
}
.eb-nav-link:hover { background: var(--eb-surface); color: var(--eb-navy); }
.eb-nav-link.active { color: var(--eb-green-dark); font-weight: 600; }
.eb-nav-actions { display: flex; align-items: center; gap: .5rem; }
.eb-nav-lang {
    font-size: .78rem; font-weight: 500; color: var(--eb-muted);
    border: 1px solid var(--eb-border); border-radius: var(--eb-radius);
    padding: .3rem .65rem; cursor: pointer;
    transition: all .15s;
}
.eb-nav-lang:hover { border-color: var(--eb-green); color: var(--eb-green-dark); }

/* BUTTONS */
.eb-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .65rem 1.4rem;
    font-size: .88rem; font-weight: 600;
    border-radius: var(--eb-radius);
    cursor: pointer; border: none;
    transition: all .2s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.eb-btn-primary {
    background: var(--eb-green); color: #fff;
}
.eb-btn-primary:hover { background: var(--eb-green-dark); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(22,163,74,.3); }
.eb-btn-outline {
    background: transparent; color: var(--eb-navy);
    border: 1.5px solid var(--eb-border);
}
.eb-btn-outline:hover { border-color: var(--eb-green); color: var(--eb-green-dark); }
.eb-btn-blue { background: var(--eb-blue); color: #fff; }
.eb-btn-blue:hover { background: var(--eb-blue-mid); }
.eb-btn-sm { padding: .45rem 1rem; font-size: .8rem; }
.eb-btn-lg { padding: .85rem 2rem; font-size: .95rem; }

/* SECTION CONTAINERS */
.eb-section { padding: 5rem 2rem; }
.eb-section-sm { padding: 3rem 2rem; }
.eb-container { max-width: 1140px; margin: 0 auto; }
.eb-container-sm { max-width: 760px; margin: 0 auto; }

/* SECTION LABELS */
.eb-eyebrow {
    font-size: .72rem; font-weight: 600;
    letter-spacing: .12em; text-transform: uppercase;
    color: var(--eb-green-dark); margin-bottom: .75rem;
    display: flex; align-items: center; gap: .5rem;
}
.eb-eyebrow::before {
    content: ''; display: block;
    width: 20px; height: 2px;
    background: var(--eb-green);
}

/* HEADINGS */
.eb-h1 { font-family: 'Syne', sans-serif; font-size: clamp(2.2rem, 4vw, 3.5rem); font-weight: 700; line-height: 1.1; color: var(--eb-navy); }
.eb-h2 { font-family: 'Syne', sans-serif; font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 700; line-height: 1.2; color: var(--eb-navy); }
.eb-h3 { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 600; color: var(--eb-navy); }
.eb-lead { font-size: 1.05rem; color: var(--eb-muted); max-width: 58ch; line-height: 1.7; }

/* CARDS */
.eb-card {
    background: #fff;
    border: 1px solid var(--eb-border);
    border-radius: var(--eb-radius-lg);
    padding: 1.75rem;
    box-shadow: var(--eb-shadow);
    transition: all .2s;
}
.eb-card:hover { box-shadow: var(--eb-shadow-lg); transform: translateY(-2px); }

/* BADGE */
.eb-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .75rem;
    border-radius: 9999px;
    font-size: .72rem; font-weight: 600;
}
.eb-badge-green { background: var(--eb-green-pale); color: var(--eb-green-dark); border: 1px solid #bbf7d0; }
.eb-badge-blue { background: var(--eb-blue-light); color: var(--eb-blue); border: 1px solid #bfdbfe; }
.eb-badge-slate { background: #f1f5f9; color: var(--eb-slate); border: 1px solid var(--eb-border); }
.eb-badge-orange { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

/* DIVIDER */
.eb-divider { border: none; border-top: 1px solid var(--eb-border); margin: 2rem 0; }

/* FOOTER */
.eb-footer {
    background: var(--eb-navy);
    color: rgba(255,255,255,.6);
    padding: 3rem 2rem 1.5rem;
}
.eb-footer-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, 1fr);
    gap: 2rem;
    max-width: 1140px; margin: 0 auto;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.eb-footer-brand { color: #fff; font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; margin-bottom: .5rem; }
.eb-footer-desc { font-size: .82rem; line-height: 1.6; }
.eb-footer-col-title { color: #fff; font-size: .8rem; font-weight: 600; margin-bottom: .75rem; letter-spacing: .04em; }
.eb-footer-link { display: block; font-size: .82rem; padding: .2rem 0; color: rgba(255,255,255,.5); transition: color .15s; }
.eb-footer-link:hover { color: var(--eb-green); }
.eb-footer-bottom {
    max-width: 1140px; margin: 1.25rem auto 0;
    display: flex; justify-content: space-between; align-items: center;
    font-size: .75rem; flex-wrap: wrap; gap: .5rem;
}
.eb-footer-powered { display: flex; align-items: center; gap: .4rem; }
.eb-footer-powered a { color: var(--eb-green); font-weight: 500; }

/* ── GRID UTILS ───────────────────────────────────────────────── */
.eb-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
.eb-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.eb-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
.eb-grid-5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; }

/* ── RESPONSIVE — VitePress-style sidebar ─────────────────────── */

/* Hamburger button : hidden on desktop */
.eb-hamburger {
    display: none;
    position: fixed; top: .75rem; left: .75rem; z-index: 300;
    width: 40px; height: 40px;
    background: var(--eb-navy); color: #fff;
    border: none; border-radius: var(--eb-radius);
    cursor: pointer; align-items: center; justify-content: center;
    font-size: 1.1rem; box-shadow: 0 2px 8px rgba(0,0,0,.3);
    transition: background .15s;
}
.eb-hamburger:hover { background: var(--eb-green-dark); }

/* Overlay backdrop */
.eb-nav-overlay {
    display: none;
    position: fixed; inset: 0; z-index: 150;
    background: rgba(0,0,0,.45);
    backdrop-filter: blur(2px);
}
.eb-nav-overlay.open { display: block; }

@media (max-width: 900px) {
    /* Grids collapse */
    .eb-grid-3, .eb-grid-4, .eb-grid-5 { grid-template-columns: repeat(2, 1fr); }
    .eb-footer-grid { grid-template-columns: 1fr 1fr; }

    /* Shell becomes single column, sidebar slides over */
    .eb-cdc-shell { flex-direction: column; }

    /* Sidebar : fixed drawer, slides in from left */
    .eb-cdc-nav {
        position: fixed; top: 0; left: 0; bottom: 0;
        width: 280px; z-index: 200;
        transform: translateX(-100%);
        transition: transform .28s cubic-bezier(.4,0,.2,1);
        box-shadow: none;
    }
    .eb-cdc-nav.open {
        transform: translateX(0);
        box-shadow: 4px 0 24px rgba(0,0,0,.3);
    }

    /* Main takes full width */
    .eb-cdc-main { width: 100%; }

    /* Show hamburger */
    .eb-hamburger { display: flex; }

    /* Push page content down to avoid hamburger overlap on page label */
    .eb-cdc-info-strip { padding-left: 3.5rem; }

    /* Nav bar in pages */
    .eb-nav { padding: 0 1rem 0 3.5rem; }
    .eb-nav-links { display: none; }

    /* Hero grids collapse */
    .eb-hero-bg > div > div[style*="grid-template-columns:1fr 420px"],
    .eb-hero-bg > div > div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 600px) {
    .eb-grid-2, .eb-grid-3, .eb-grid-4, .eb-grid-5 { grid-template-columns: 1fr; }
    .eb-footer-grid { grid-template-columns: 1fr; }
    .eb-section { padding: 3rem 1rem; }
    .eb-section-sm { padding: 2rem 1rem; }
    .eb-container { padding: 0; }
    .eb-stat-strip { padding: 2rem 1rem; }
    .eb-nav { height: 52px; padding: 0 1rem 0 3.5rem; }
    .eb-cdc-nav { width: 100%; max-width: 320px; }
    .eb-pricing-card.featured::before { font-size: .6rem; }
    /* Force single col on all inline grids in pages */
    [style*="grid-template-columns:1fr 1fr"],
    [style*="grid-template-columns:1fr 420px"],
    [style*="grid-template-columns:1fr 1.6fr"],
    [style*="grid-template-columns:1.5fr repeat(3, 1fr)"],
    [style*="grid-template-columns:repeat(3,1fr)"],
    [style*="grid-template-columns:repeat(4,1fr)"],
    [style*="grid-template-columns:repeat(2,1fr)"] {
        grid-template-columns: 1fr !important;
    }
    .eb-dash-nav { flex-wrap: wrap; gap: .2rem; height: auto; padding: .5rem; }
    .eb-dash-body { padding: 1rem; }
    .eb-widget-val { font-size: 1.4rem; }
}

/* STAT STRIP */
.eb-stat-strip {
    background: var(--eb-navy);
    padding: 2.5rem 2rem;
}
.eb-stat-item { text-align: center; }
.eb-stat-val { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 700; color: var(--eb-green); }
.eb-stat-label { font-size: .82rem; color: rgba(255,255,255,.55); margin-top: .2rem; }

/* SERVICE CARD */
.eb-service-card {
    background: #fff;
    border: 1px solid var(--eb-border);
    border-radius: var(--eb-radius-lg);
    padding: 1.5rem;
    display: flex; flex-direction: column;
    gap: .75rem;
    transition: all .2s;
    position: relative; overflow: hidden;
}
.eb-service-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; background: var(--eb-green);
    transform: scaleX(0); transform-origin: left;
    transition: transform .3s;
}
.eb-service-card:hover::before { transform: scaleX(1); }
.eb-service-card:hover { box-shadow: var(--eb-shadow-lg); transform: translateY(-3px); }
.eb-service-icon {
    width: 48px; height: 48px;
    background: var(--eb-green-pale);
    border-radius: var(--eb-radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.eb-service-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 600; color: var(--eb-navy); }
.eb-service-desc { font-size: .84rem; color: var(--eb-muted); line-height: 1.6; }
.eb-service-link { font-size: .8rem; font-weight: 600; color: var(--eb-green-dark); display: flex; align-items: center; gap: .3rem; margin-top: auto; }

/* FEATURE ROW */
.eb-feature-row { display: flex; gap: .75rem; align-items: flex-start; padding: .75rem 0; border-bottom: 1px solid var(--eb-border); }
.eb-feature-row:last-child { border-bottom: none; }
.eb-feature-icon { width: 36px; height: 36px; border-radius: var(--eb-radius); background: var(--eb-green-pale); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.eb-feature-body {}
.eb-feature-title { font-weight: 600; font-size: .9rem; color: var(--eb-navy); }
.eb-feature-desc { font-size: .82rem; color: var(--eb-muted); margin-top: .15rem; }

/* FORM */
.eb-form-group { margin-bottom: 1.1rem; }
.eb-form-label { display: block; font-size: .82rem; font-weight: 500; color: var(--eb-slate); margin-bottom: .4rem; }
.eb-form-input, .eb-form-select, .eb-form-textarea {
    width: 100%; padding: .7rem .9rem;
    border: 1.5px solid var(--eb-border);
    border-radius: var(--eb-radius);
    font-size: .88rem; font-family: 'Plus Jakarta Sans', sans-serif;
    color: var(--eb-navy);
    background: #fff;
    outline: none;
    transition: border-color .15s;
}
.eb-form-input:focus, .eb-form-select:focus, .eb-form-textarea:focus { border-color: var(--eb-green); box-shadow: 0 0 0 3px rgba(34,197,94,.1); }
.eb-form-textarea { resize: vertical; min-height: 100px; }

/* PROCESS STEPS */
.eb-steps { display: flex; flex-direction: column; gap: 0; }
.eb-step { display: flex; gap: 1.25rem; padding: 1.25rem 0; border-bottom: 1px solid var(--eb-border); }
.eb-step:last-child { border-bottom: none; }
.eb-step-num {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--eb-green); color: #fff;
    font-size: .82rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-family: 'Syne', sans-serif;
}
.eb-step-body {}
.eb-step-title { font-weight: 600; color: var(--eb-navy); font-size: .9rem; }
.eb-step-desc { font-size: .82rem; color: var(--eb-muted); margin-top: .2rem; }

/* DASHBOARD */
.eb-dash-wrap { background: var(--eb-surface); min-height: 100vh; }
.eb-dash-nav {
    background: var(--eb-navy); padding: 0 1.5rem;
    display: flex; align-items: center; gap: 1rem;
    height: 56px; border-bottom: 1px solid rgba(255,255,255,.08);
}
.eb-dash-nav-link { font-size: .82rem; color: rgba(255,255,255,.55); padding: .4rem .75rem; border-radius: var(--eb-radius); transition: all .15s; display: flex; align-items: center; gap: .4rem; }
.eb-dash-nav-link:hover, .eb-dash-nav-link.active { color: #fff; background: rgba(255,255,255,.08); }
.eb-dash-nav-link.active { color: var(--eb-green); }
.eb-dash-body { padding: 2rem 1.5rem; max-width: 1100px; }
.eb-widget {
    background: #fff; border: 1px solid var(--eb-border);
    border-radius: var(--eb-radius-lg);
    padding: 1.25rem 1.5rem;
    box-shadow: var(--eb-shadow);
}
.eb-widget-label { font-size: .72rem; color: var(--eb-muted); font-weight: 500; letter-spacing: .04em; text-transform: uppercase; }
.eb-widget-val { font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 700; color: var(--eb-navy); margin: .2rem 0; }
.eb-widget-sub { font-size: .78rem; color: var(--eb-muted); }
.eb-widget-icon { font-size: 1.5rem; margin-bottom: .5rem; }
.eb-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.eb-table thead tr { border-bottom: 2px solid var(--eb-border); }
.eb-table th { padding: .6rem .9rem; text-align: left; font-size: .72rem; font-weight: 600; color: var(--eb-muted); letter-spacing: .05em; text-transform: uppercase; }
.eb-table td { padding: .7rem .9rem; border-bottom: 1px solid var(--eb-border); vertical-align: middle; }
.eb-table tr:last-child td { border-bottom: none; }
.eb-status { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .6rem; border-radius: 9999px; font-size: .72rem; font-weight: 600; }
.eb-status-pending { background: #fff7ed; color: #c2410c; }
.eb-status-active { background: var(--eb-green-pale); color: var(--eb-green-dark); }
.eb-status-draft { background: #f1f5f9; color: var(--eb-muted); }

/* SITEMAP */
.eb-sitemap-node {
    background: #fff; border: 1.5px solid var(--eb-border);
    border-radius: var(--eb-radius); padding: .5rem 1rem;
    font-size: .82rem; font-weight: 500; color: var(--eb-navy);
    text-align: center; white-space: nowrap;
    box-shadow: var(--eb-shadow);
    transition: all .15s;
    cursor: default;
}
.eb-sitemap-node:hover { border-color: var(--eb-green); box-shadow: 0 4px 12px rgba(34,197,94,.15); }
.eb-sitemap-node.root { background: var(--eb-navy); color: #fff; border-color: var(--eb-navy); font-family: 'Syne', sans-serif; font-weight: 700; }
.eb-sitemap-node.private { background: var(--eb-blue-light); border-color: #93c5fd; color: var(--eb-blue); }
.eb-sitemap-node.service { border-color: #bbf7d0; color: var(--eb-green-dark); }

/* HERO GRADIENT */
.eb-hero-bg {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    position: relative; overflow: hidden;
}
.eb-hero-bg::before {
    content: '';
    position: absolute; top: -30%; right: -10%;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(34,197,94,.12) 0%, transparent 70%);
    pointer-events: none;
}
.eb-hero-bg::after {
    content: '';
    position: absolute; bottom: -20%; left: 5%;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(30,58,138,.2) 0%, transparent 70%);
    pointer-events: none;
}

/* TESTIMONIAL / QUOTE BLOCK */
.eb-quote {
    border-left: 3px solid var(--eb-green);
    padding: 1rem 1.5rem;
    background: var(--eb-green-pale);
    border-radius: 0 var(--eb-radius) var(--eb-radius) 0;
    font-style: italic;
    color: var(--eb-slate);
    font-size: .95rem;
    line-height: 1.7;
}

/* PRICING TABLE */
.eb-pricing-card {
    border: 1.5px solid var(--eb-border);
    border-radius: var(--eb-radius-lg);
    padding: 2rem;
    background: #fff;
    transition: all .2s;
}
.eb-pricing-card.featured {
    border-color: var(--eb-green);
    box-shadow: 0 8px 32px rgba(34,197,94,.15);
    position: relative;
}
.eb-pricing-card.featured::before {
    content: 'Recommandé';
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: var(--eb-green); color: #fff;
    font-size: .68rem; font-weight: 700;
    padding: .2rem .75rem; border-radius: 9999px;
    letter-spacing: .06em; text-transform: uppercase;
}
.eb-pricing-price { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 700; color: var(--eb-navy); }
.eb-pricing-period { font-size: .82rem; color: var(--eb-muted); }
.eb-pricing-feature { display: flex; align-items: center; gap: .5rem; font-size: .84rem; padding: .4rem 0; color: var(--eb-slate); }
.eb-pricing-feature::before { content: '✓'; color: var(--eb-green); font-weight: 700; flex-shrink: 0; }

/* ABOUT PHOTO PLACEHOLDER */
.eb-photo-placeholder {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    border-radius: var(--eb-radius-lg);
    display: flex; align-items: center; justify-content: center;
    color: var(--eb-muted); font-size: .85rem;
    aspect-ratio: 4/5;
}

/* MAP PLACEHOLDER */
.eb-map-placeholder {
    background: linear-gradient(135deg, #e2e8f0, #dde4ed);
    border-radius: var(--eb-radius-lg);
    display: flex; align-items: center; justify-content: center;
    color: var(--eb-muted); font-size: .85rem;
    height: 240px;
    border: 1px solid var(--eb-border);
    position: relative; overflow: hidden;
}
.eb-map-pin { font-size: 2rem; }

/* CDC INFO STRIP */
.eb-cdc-info-strip {
    background: var(--eb-blue); color: #fff;
    padding: .5rem 1.5rem;
    font-size: .72rem; display: flex; align-items: center; gap: 1rem;
    flex-wrap: wrap;
}
.eb-cdc-info-strip strong { color: #93c5fd; }

/* TAGS */
.eb-tag { display: inline-block; background: var(--eb-surface); border: 1px solid var(--eb-border); border-radius: var(--eb-radius); padding: .2rem .65rem; font-size: .75rem; color: var(--eb-muted); margin: .15rem; }
</style>

{{-- ══════════════════════════════════════════════════════════════
     CDC SHELL — Sidebar + Page Panels
══════════════════════════════════════════════════════════════ --}}
{{-- ── Hamburger + Overlay — VitePress-style sidebar mobile ──────
     Alpine gère l'état navOpen.
     Le bouton est fixed en haut à gauche, visible uniquement ≤ 900px.
──────────────────────────────────────────────────────────────── --}}
<div class="eb-proto" x-data="{ page: 'home', navOpen: false }">

{{-- Overlay backdrop --}}
<div class="eb-nav-overlay" :class="navOpen && 'open'" @click="navOpen = false"></div>

{{-- Hamburger button --}}
<button class="eb-hamburger" @click="navOpen = !navOpen" :aria-label="navOpen ? 'Fermer le menu' : 'Ouvrir le menu'">
    <span x-text="navOpen ? '✕' : '☰'"></span>
</button>

<div class="eb-cdc-shell">

    {{-- ── SIDEBAR ──────────────────────────────────────────────── --}}
    <nav class="eb-cdc-nav" :class="navOpen && 'open'">
        <div class="eb-cdc-nav-logo">
            <div class="eb-cdc-nav-logo-mark">
                <div class="eb-logo-square">EB</div>
                EASBridge
            </div>
        </div>
        <div class="eb-cdc-nav-meta">
            CDC Visuel · Module <code style="color:rgba(255,255,255,.5)">easbridge-site</code><br>
            Accord 19CQRCFDP · v1.1
        </div>

        <div class="eb-cdc-nav-section">Pages publiques</div>
        <div class="eb-cdc-nav-item" :class="page === 'home' && 'active'" @click="page = 'home'; navOpen = false">
            <span class="dot"></span> Home
            <span class="eb-cdc-nav-badge">EN/ES</span>
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'services' && 'active'" @click="page = 'services'; navOpen = false">
            <span class="dot"></span> Services — Hub
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'steri' && 'active'" @click="page = 'steri'; navOpen = false">
            <span class="dot"></span> ↳ Stérilisation
            <span class="eb-cdc-nav-badge" style="background:#c2410c">P1</span>
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'nettoyage' && 'active'" @click="page = 'nettoyage'; navOpen = false">
            <span class="dot"></span> ↳ Nettoyage
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'complements' && 'active'" @click="page = 'complements'; navOpen = false">
            <span class="dot"></span> ↳ Compléments
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'webkernel' && 'active'" @click="page = 'webkernel'; navOpen = false">
            <span class="dot"></span> ↳ Webkernel
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'travel' && 'active'" @click="page = 'travel'; navOpen = false">
            <span class="dot"></span> ↳ Travel
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'about' && 'active'" @click="page = 'about'; navOpen = false">
            <span class="dot"></span> À propos
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'blog' && 'active'" @click="page = 'blog'; navOpen = false">
            <span class="dot"></span> Blog
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'contact' && 'active'" @click="page = 'contact'; navOpen = false">
            <span class="dot"></span> Contact / Devis
        </div>

        <div class="eb-cdc-nav-section" style="margin-top:.5rem">Espace partenaire</div>
        <div class="eb-cdc-nav-item" :class="page === 'login' && 'active'" @click="page = 'login'; navOpen = false">
            <span class="dot"></span> Login partenaire
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'dashboard' && 'active'" @click="page = 'dashboard'; navOpen = false">
            <span class="dot"></span> Dashboard
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'tenders' && 'active'" @click="page = 'tenders'; navOpen = false">
            <span class="dot"></span> Appels d'offres
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'commissions' && 'active'" @click="page = 'commissions'; navOpen = false">
            <span class="dot"></span> Commissions
        </div>
        <div class="eb-cdc-nav-item" :class="page === 'docs' && 'active'" @click="page = 'docs'; navOpen = false">
            <span class="dot"></span> Documents
        </div>

        <div class="eb-cdc-nav-section" style="margin-top:.5rem">Architecture</div>
        <div class="eb-cdc-nav-item" :class="page === 'sitemap' && 'active'" @click="page = 'sitemap'; navOpen = false">
            <span class="dot"></span> Sitemap interactif
        </div>

        <div style="margin-top:auto;padding:1rem 1.25rem;font-size:.68rem;color:rgba(255,255,255,.25);border-top:1px solid rgba(255,255,255,.06);">
            🔒 Confidentiel — Numerimondes × EASBridge
        </div>
    </nav>

    {{-- ── MAIN PANELS ──────────────────────────────────────────── --}}
    <div class="eb-cdc-main">

{{-- ════════════════════════════════════════════════════════════
     PAGE : HOME
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'home' && 'active'">
    <div class="eb-cdc-info-strip">
        Route : <strong>/</strong> &nbsp;·&nbsp;
        Blocks : <strong>HeroBlock · TrustBarBlock · ServiceGridBlock · StatsRowBlock · TextImageBlock · CtaBannerBlock</strong> &nbsp;·&nbsp;
        Locales : <strong>EN · ES</strong>
    </div>

    {{-- NAV --}}
    <nav class="eb-nav">
        <div class="eb-nav-logo">
            <div class="eb-logo-square">EB</div>
            <span class="eb-nav-logo-text">EASBridge</span>
        </div>
        <div class="eb-nav-links">
            <a class="eb-nav-link active" href="#">Home</a>
            <a class="eb-nav-link" href="#">Services</a>
            <a class="eb-nav-link" href="#">About</a>
            <a class="eb-nav-link" href="#">Blog</a>
            <a class="eb-nav-link" href="#">Contact</a>
        </div>
        <div class="eb-nav-actions">
            <span class="eb-nav-lang">EN</span>
            <span class="eb-nav-lang">ES</span>
            <button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button>
        </div>
    </nav>

    {{-- HERO --}}
    <div class="eb-hero-bg" style="padding: 6rem 2rem 5rem; color:#fff; position:relative; z-index:1;">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 420px;gap:4rem;align-items:center;">
            <div>
                <div class="eb-eyebrow" style="color:var(--eb-green);">Barcelona · Spain — Europe</div>
                <h1 class="eb-h1" style="color:#fff;font-size:clamp(2.4rem,4.5vw,3.8rem);margin-bottom:1.25rem;">
                    Your operational<br>
                    environment,<br>
                    <span style="color:var(--eb-green)">fully managed.</span>
                </h1>
                <p class="eb-lead" style="color:rgba(255,255,255,.65);margin-bottom:2rem;max-width:50ch;">
                    Sterilisation · Cleaning · Software · Sourcing · Travel —
                    one dedicated partner from Barcelona to Europe.
                    Structure agile. Réactivité garantie.
                </p>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <button class="eb-btn eb-btn-primary eb-btn-lg">Request a quote →</button>
                    <button class="eb-btn eb-btn-outline eb-btn-lg" style="color:#fff;border-color:rgba(255,255,255,.25);">Discover our services</button>
                </div>
            </div>
            <div>
                <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:var(--eb-radius-lg);padding:1.75rem;backdrop-filter:blur(8px);">
                    <div style="font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.1em;margin-bottom:1rem;">Active sectors</div>
                    <div style="display:flex;flex-direction:column;gap:.6rem;">
                        @foreach([
                            ['🏥', 'Hospital Sterilisation', 'Spain · LCSP compliant'],
                            ['✨', 'Premium Cleaning', 'Hospitality · Costa del Sol'],
                            ['💊', 'Supplements & Pharma', 'Morocco · Sahel · EU'],
                            ['🖥️', 'Webkernel Software', 'Official EU reseller'],
                            ['✈️', 'Travel & Concierge', 'Corporate · 24/7'],
                        ] as [$icon, $title, $sub])
                        <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem;background:rgba(255,255,255,.04);border-radius:var(--eb-radius);border:1px solid rgba(255,255,255,.06);">
                            <span style="font-size:1.1rem;">{{ $icon }}</span>
                            <div>
                                <div style="font-size:.84rem;font-weight:600;color:#fff;">{{ $title }}</div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.4);">{{ $sub }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TRUST BAR --}}
    <div style="border-bottom:1px solid var(--eb-border);padding:1.25rem 2rem;background:#fff;">
        <div class="eb-container" style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;justify-content:space-between;">
            <span style="font-size:.75rem;color:var(--eb-muted);font-weight:500;letter-spacing:.06em;text-transform:uppercase;">Sectors we operate in</span>
            @foreach(['🏥 Healthcare','🏨 Hospitality','💊 Pharma & Nutrition','⚖️ Public Tenders LCSP','🖥 Webkernel Partner EU'] as $trust)
            <div style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;font-weight:500;color:var(--eb-slate);">{{ $trust }}</div>
            @endforeach
        </div>
    </div>

    {{-- SERVICES GRID --}}
    <div class="eb-section" style="background:var(--eb-surface);">
        <div class="eb-container">
            <div style="text-align:center;margin-bottom:3rem;">
                <div class="eb-eyebrow" style="justify-content:center;">Our five pillars</div>
                <h2 class="eb-h2">Everything your organisation needs.<br>One dedicated partner.</h2>
            </div>
            <div class="eb-grid-3" style="grid-template-columns:repeat(3,1fr);">
                @foreach([
                    ['🏥','Hospital Sterilisation','Full sterilisation management for hospitals, clinics and surgical centres. EN ISO 17665-1 compliant, CE-certified, fully traceable.','Learn more →','service'],
                    ['✨','Premium Cleaning','Eco-certified, discreet cleaning for luxury hotels and residences. Night operations. Dedicated supervisor.','Learn more →','service'],
                    ['💊','Food & Pharma Supplements','EU-sourced, ONSSA-certified supplements. Distribution Morocco, Sahel, Europe.','Learn more →','service'],
                    ['🖥️','Webkernel Software','CRM, ERP, Medtech, SaaS — open-source PHP. Official reseller for Spain & Europe.','Request a demo →','service'],
                    ['✈️','Travel & Concierge','End-to-end business travel management. 24/7 emergency support. Corporate policy management.','Learn more →','service'],
                ] as [$icon,$title,$desc,$cta,$type])
                <div class="eb-service-card">
                    <div class="eb-service-icon">{{ $icon }}</div>
                    <div class="eb-service-title">{{ $title }}</div>
                    <div class="eb-service-desc">{{ $desc }}</div>
                    <div class="eb-service-link">{{ $cta }} <span>›</span></div>
                </div>
                @endforeach
                {{-- 6th card: Contact --}}
                <div class="eb-service-card" style="background:var(--eb-navy);border-color:var(--eb-navy);justify-content:center;align-items:center;text-align:center;gap:1rem;">
                    <div style="font-size:1.75rem;">🤝</div>
                    <div class="eb-service-title" style="color:#fff;">Not sure which service?</div>
                    <div class="eb-service-desc" style="color:rgba(255,255,255,.55);">Tell us your need — we'll find the right approach together.</div>
                    <button class="eb-btn eb-btn-primary" style="margin-top:.5rem;">Contact us →</button>
                </div>
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="eb-stat-strip">
        <div class="eb-container">
            <div class="eb-grid-4">
                @foreach([
                    ['2','Markets','Spain + Morocco'],
                    ['5','Pillars','End-to-end operational coverage'],
                    ['< 4h','Response','Emergency sterilisation SLA'],
                    ['EU','Partner','Webkernel official reseller'],
                ] as [$val,$label,$sub])
                <div class="eb-stat-item">
                    <div class="eb-stat-val">{{ $val }}</div>
                    <div class="eb-stat-label" style="font-weight:600;color:rgba(255,255,255,.8);">{{ $label }}</div>
                    <div class="eb-stat-label" style="font-size:.72rem;">{{ $sub }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TEXT + IMAGE --}}
    <div class="eb-section">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;">
            <div>
                <div class="eb-eyebrow">Why EASBridge</div>
                <h2 class="eb-h2" style="margin-bottom:1rem;">Agility that large groups cannot offer.</h2>
                <p style="color:var(--eb-muted);font-size:.95rem;line-height:1.75;margin-bottom:1.5rem;">
                    EASBridge specialises in managing the operational environment of organisations. Whether you need sterilisation, facility services, software tools or supply chain support — we understand how these dimensions connect, and act with a dedicated team and response times no large firm can match.
                </p>
                <div class="eb-quote" style="margin-bottom:1.75rem;">
                    One project, one team, one contact — from day one to delivery.
                </div>
                <button class="eb-btn eb-btn-blue">Learn about EASBridge →</button>
            </div>
            <div style="display:flex;flex-direction:column;gap:1rem;">
                @foreach([
                    ['🎯','Dedicated structure','No project overload. Your contract, our full attention.'],
                    ['⚡','24h response guarantee','On every request — 4h for sterilisation emergencies.'],
                    ['🌍','Spain · Morocco · Europe','Operational coverage across key markets.'],
                    ['🔷','Webkernel technology','Sovereign software — no vendor lock-in.'],
                ] as [$icon,$title,$desc])
                <div class="eb-feature-row">
                    <div class="eb-feature-icon">{{ $icon }}</div>
                    <div class="eb-feature-body">
                        <div class="eb-feature-title">{{ $title }}</div>
                        <div class="eb-feature-desc">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- CTA BANNER --}}
    <div style="background:var(--eb-green);padding:4rem 2rem;text-align:center;">
        <div class="eb-container-sm">
            <h2 class="eb-h2" style="color:#fff;margin-bottom:.75rem;">Ready to submit a tender or source a solution?</h2>
            <p style="color:rgba(255,255,255,.8);margin-bottom:2rem;">We respond within 24 hours. No commitments required.</p>
            <div style="display:flex;gap:1rem;justify-content:center;">
                <button class="eb-btn" style="background:#fff;color:var(--eb-green-dark);font-weight:700;">Contact us today →</button>
                <button class="eb-btn" style="background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.4);">Discover services</button>
            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="eb-footer">
        <div class="eb-footer-grid">
            <div>
                <div class="eb-footer-brand">🔷 EASBridge</div>
                <div class="eb-footer-desc">Operational partner for healthcare, hospitality and digital organisations across Spain and Europe.<br><br>C/ Ataulfo, 08002 Barcelona, España</div>
            </div>
            <div>
                <div class="eb-footer-col-title">Services</div>
                @foreach(['Hospital Sterilisation','Premium Cleaning','Supplements & Pharma','Webkernel Software','Travel & Concierge'] as $s)
                <a class="eb-footer-link" href="#">{{ $s }}</a>
                @endforeach
            </div>
            <div>
                <div class="eb-footer-col-title">Company</div>
                @foreach(['About EASBridge','Blog & Insights','Contact','Partner Login'] as $s)
                <a class="eb-footer-link" href="#">{{ $s }}</a>
                @endforeach
            </div>
            <div>
                <div class="eb-footer-col-title">Partners</div>
                <a class="eb-footer-link" href="#">numerimondes.com</a>
                <a class="eb-footer-link" href="#">webkernelphp.com</a>
                <div style="margin-top:1rem;"><span class="eb-badge eb-badge-green">Official Webkernel Reseller EU</span></div>
            </div>
        </div>
        <div class="eb-footer-bottom">
            <span>© {{ date('Y') }} EASBridge · Taha Laamrani · Barcelona</span>
            <div class="eb-footer-powered">Powered by <a href="#">Webkernel™</a> · Built by <a href="#">Numerimondes</a></div>
        </div>
    </footer>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : SERVICES HUB
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'services' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/services</strong> &nbsp;·&nbsp; Block : <strong>HeroBlock · ServiceGridBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link active" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Blog</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button></div></nav>

    <div style="background:var(--eb-surface);padding:4rem 2rem 3rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container">
            <div class="eb-eyebrow">What we do</div>
            <h1 class="eb-h1" style="margin-bottom:.75rem;">Our Services</h1>
            <p class="eb-lead">Five pillars. One dedicated partner. From Barcelona to Europe.</p>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;">
                @foreach([
                    ['🏥','Hospital Sterilisation','/services/sterilisation','Full sterilisation management for public and private healthcare facilities. EN ISO 17665-1 / EN ISO 11135. CE certified. 4h emergency response.','EN ISO 17665-1','Healthcare','steri'],
                    ['✨','Premium Cleaning','/services/nettoyage','Eco-certified, discreet cleaning for luxury hotels and residences. Night operations 23:00–06:00. Dedicated supervisor. Weekly audits.','Eco-certified','Hospitality','nettoyage'],
                    ['💊','Supplements & Pharma','/services/complements-alimentaires','EU-sourced food and pharmaceutical supplements. ONSSA-certified export to Morocco, Sahel and international markets.','ONSSA certified','Health & Nutrition','complements'],
                    ['🖥️','Webkernel Software','/services/logiciels-webkernel','CRM, ERP, Medtech, SaaS platforms built on the open-source Webkernel kernel. Official reseller for Spain & Europe. Free core — perpetual licences.','Official Reseller EU','Digital / SaaS','webkernel'],
                    ['✈️','Travel & Concierge','/services/travel-conciergerie','End-to-end business travel management. Corporate policy. Emergency support 24/7. Delegation & event logistics.','24/7 support','Mobility','travel'],
                ] as [$icon,$title,$url,$desc,$tag,$sector,$pg])
                <div class="eb-service-card" style="cursor:pointer;" @click="page = '{{ $pg }}'">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div class="eb-service-icon">{{ $icon }}</div>
                        <span class="eb-badge eb-badge-slate">{{ $sector }}</span>
                    </div>
                    <div class="eb-service-title">{{ $title }}</div>
                    <div class="eb-service-desc">{{ $desc }}</div>
                    <div style="margin-top:.5rem;"><span class="eb-badge eb-badge-green">{{ $tag }}</span></div>
                    <div class="eb-service-link" style="margin-top:.75rem;">View service <span>›</span></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div style="background:var(--eb-navy);padding:3rem 2rem;text-align:center;">
        <div class="eb-container-sm">
            <h2 class="eb-h2" style="color:#fff;margin-bottom:.5rem;">Not sure which service fits your need?</h2>
            <p style="color:rgba(255,255,255,.6);margin-bottom:1.5rem;">Describe your situation — we'll find the right approach together within 24 hours.</p>
            <button class="eb-btn eb-btn-primary eb-btn-lg">Contact EASBridge →</button>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : STÉRILISATION (prioritaire)
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'steri' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/services/sterilisation</strong> &nbsp;·&nbsp; Blocks : <strong>HeroBlock · FeatureGridBlock · ProcessStepsBlock · QuoteFormBlock · CtaBannerBlock</strong> &nbsp;·&nbsp; <strong>🔴 Priorité commerciale #1</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link active" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button></div></nav>

    <div class="eb-hero-bg" style="padding:5rem 2rem 4rem;">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;">
            <div>
                <div class="eb-eyebrow" style="color:var(--eb-green);">Healthcare · LCSP compliant</div>
                <h1 class="eb-h1" style="color:#fff;margin-bottom:1rem;">Hospital Sterilisation<br><span style="color:var(--eb-green);">Services</span></h1>
                <p class="eb-lead" style="color:rgba(255,255,255,.65);margin-bottom:1.5rem;max-width:46ch;">Dedicated sterilisation management for hospitals, clinics and surgical centres. Fully compliant, fully traceable, fully dedicated.</p>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:2rem;">
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">EN ISO 17665-1</span>
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">EN ISO 11135</span>
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">CE Certified</span>
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">LCSP</span>
                </div>
                <button class="eb-btn eb-btn-primary eb-btn-lg">Request a quote →</button>
            </div>
            <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:var(--eb-radius-lg);padding:1.75rem;">
                <div style="font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.1em;margin-bottom:1rem;">Our commitment</div>
                @foreach([
                    ['⚡','4h emergency response','Dedicated team, on-call for critical interventions'],
                    ['📋','Full cycle traceability','Documentation per instrument batch, every cycle'],
                    ['🎓','Staff training included','Initial onboarding + quarterly refreshers'],
                    ['🔒','Zero concurrent projects','Your facility is our only focus during the contract'],
                ] as [$icon,$title,$desc])
                <div style="display:flex;gap:.75rem;align-items:flex-start;padding:.65rem 0;border-bottom:1px solid rgba(255,255,255,.07);">
                    <span style="font-size:1rem;flex-shrink:0;">{{ $icon }}</span>
                    <div>
                        <div style="font-size:.85rem;font-weight:600;color:#fff;">{{ $title }}</div>
                        <div style="font-size:.75rem;color:rgba(255,255,255,.45);">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start;">
            <div>
                <div class="eb-eyebrow">How we work</div>
                <h2 class="eb-h2" style="margin-bottom:1.5rem;">Our sterilisation methodology</h2>
                <div class="eb-steps">
                    @foreach([
                        ['01','Site assessment','On-site audit of instruments, volumes, infrastructure and existing protocols.'],
                        ['02','Custom protocol design','Tailored sterilisation cycles, validation plans and traceability systems.'],
                        ['03','Deployment & training','Equipment setup, staff training and initial validation runs.'],
                        ['04','Ongoing operations','Full cycle management, documentation, quality audits, incident reporting.'],
                        ['05','Regulatory compliance','Complete LCSP/EU documentation package for public tender compliance.'],
                    ] as [$num,$title,$desc])
                    <div class="eb-step">
                        <div class="eb-step-num">{{ $num }}</div>
                        <div><div class="eb-step-title">{{ $title }}</div><div class="eb-step-desc">{{ $desc }}</div></div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="eb-eyebrow">Request a quote</div>
                <h2 class="eb-h2" style="margin-bottom:1.5rem;">Tell us about your facility</h2>
                <div class="eb-form-group"><label class="eb-form-label">Organisation name *</label><input class="eb-form-input" type="text" placeholder="Hospital Universitario de Barcelona"></div>
                <div class="eb-form-group"><label class="eb-form-label">Contact name *</label><input class="eb-form-input" type="text" placeholder="Dr. María García"></div>
                <div class="eb-form-group"><label class="eb-form-label">Email *</label><input class="eb-form-input" type="email" placeholder="compras@hospital.es"></div>
                <div class="eb-form-group"><label class="eb-form-label">Facility type *</label>
                    <select class="eb-form-select"><option>Public hospital</option><option>Private clinic</option><option>Dental practice</option><option>Surgical centre</option><option>Other</option></select>
                </div>
                <div class="eb-form-group"><label class="eb-form-label">Estimated monthly volume (instruments)</label><input class="eb-form-input" type="number" placeholder="500"></div>
                <div class="eb-form-group"><label class="eb-form-label">Message *</label><textarea class="eb-form-textarea" placeholder="Describe your sterilisation needs, current challenges or procurement context..."></textarea></div>
                <button class="eb-btn eb-btn-primary" style="width:100%;">Request sterilisation quote →</button>
                <p style="font-size:.72rem;color:var(--eb-muted);margin-top:.75rem;text-align:center;">Response within 24 hours. No commitment required.</p>
            </div>
        </div>
    </div>

    <div style="background:var(--eb-surface);padding:3rem 2rem;border-top:1px solid var(--eb-border);">
        <div class="eb-container">
            <div class="eb-eyebrow" style="justify-content:center;">Target clients</div>
            <div class="eb-grid-4" style="margin-top:1.5rem;">
                @foreach(['🏥 Public Hospitals','🏗 Private Clinics','🦷 Dental Practices','🔪 Surgical Centres'] as $t)
                <div style="text-align:center;padding:1.25rem;background:#fff;border:1px solid var(--eb-border);border-radius:var(--eb-radius-lg);font-size:.88rem;font-weight:500;color:var(--eb-slate);">{{ $t }}</div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : NETTOYAGE
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'nettoyage' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/services/nettoyage</strong> &nbsp;·&nbsp; Blocks : <strong>HeroBlock · FeatureGridBlock · QuoteFormBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link active" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button></div></nav>

    <div style="background:var(--eb-surface);padding:5rem 2rem 4rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;">
            <div>
                <div class="eb-eyebrow">Hospitality · Premium</div>
                <h1 class="eb-h1" style="margin-bottom:1rem;">Premium Cleaning Services</h1>
                <p class="eb-lead" style="margin-bottom:2rem;">Eco-certified, discreet cleaning for luxury hotels, residences and healthcare facilities on the Costa del Sol and beyond.</p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem;">
                    <span class="eb-badge eb-badge-green">Eco-certified products</span>
                    <span class="eb-badge eb-badge-blue">Night operations</span>
                    <span class="eb-badge eb-badge-slate">Dedicated supervisor</span>
                </div>
                <button class="eb-btn eb-btn-primary eb-btn-lg">Request a quote →</button>
            </div>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                @foreach([
                    ['🌿','Eco-certified products only','Full technical data sheets provided on request. No harsh chemicals in client-facing areas.'],
                    ['🌙','Night operations','Interventions 23:00–06:00. Zero disruption to guests, patients or staff.'],
                    ['👤','Single point of contact','One dedicated supervisor for your property. Weekly audit report. Anomaly tracker.'],
                    ['🏨','Hospitality protocols','Luxury property standards. Silent procedures. Guest experience–centred.'],
                ] as [$icon,$title,$desc])
                <div class="eb-feature-row">
                    <div class="eb-feature-icon">{{ $icon }}</div>
                    <div><div class="eb-feature-title">{{ $title }}</div><div class="eb-feature-desc">{{ $desc }}</div></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container-sm">
            <div class="eb-eyebrow" style="justify-content:center;">Request a quote</div>
            <h2 class="eb-h2" style="text-align:center;margin-bottom:2rem;">Tell us about your property</h2>
            <div class="eb-form-group"><label class="eb-form-label">Property name *</label><input class="eb-form-input" type="text" placeholder="Club Med Marbella"></div>
            <div class="eb-grid-2">
                <div class="eb-form-group"><label class="eb-form-label">Contact name *</label><input class="eb-form-input" type="text"></div>
                <div class="eb-form-group"><label class="eb-form-label">Email *</label><input class="eb-form-input" type="email"></div>
            </div>
            <div class="eb-form-group"><label class="eb-form-label">Property type *</label>
                <select class="eb-form-select"><option>Luxury hotel</option><option>Resort</option><option>Private residence</option><option>Healthcare facility</option><option>Other</option></select>
            </div>
            <div class="eb-form-group"><label class="eb-form-label">Surface area (m²)</label><input class="eb-form-input" type="number" placeholder="2500"></div>
            <div class="eb-form-group"><label class="eb-form-label">Message</label><textarea class="eb-form-textarea" placeholder="Describe your property and cleaning requirements..."></textarea></div>
            <button class="eb-btn eb-btn-primary" style="width:100%;">Request cleaning quote →</button>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : COMPLÉMENTS
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'complements' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/services/complements-alimentaires</strong> &nbsp;·&nbsp; Blocks : <strong>HeroBlock · ProcessStepsBlock · QuoteFormBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link active" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button></div></nav>

    <div style="background:var(--eb-surface);padding:5rem 2rem 4rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container">
            <div class="eb-eyebrow">Health & Nutrition · Export</div>
            <h1 class="eb-h1" style="margin-bottom:1rem;">Food & Pharmaceutical Supplements</h1>
            <p class="eb-lead" style="margin-bottom:2rem;">European-sourced, ONSSA-certified supplements for distribution in Morocco, the Sahel and international markets. Lot-by-lot traceability.</p>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <span class="eb-badge eb-badge-green">ONSSA certified</span>
                <span class="eb-badge eb-badge-blue">EU suppliers</span>
                <span class="eb-badge eb-badge-slate">Morocco · Sahel · EU</span>
                <span class="eb-badge eb-badge-slate">Human & Veterinary</span>
            </div>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;">
            <div>
                <div class="eb-eyebrow">Export process</div>
                <h2 class="eb-h2" style="margin-bottom:1.5rem;">From EU supplier to your market</h2>
                <div class="eb-steps">
                    @foreach([
                        ['01','Product selection','Certified EU manufacturers. Lot-by-lot traceability. Certificate of analysis provided.'],
                        ['02','ONSSA certification','Regulatory filing managed by Numerimondes (Morocco). Human and veterinary categories.'],
                        ['03','Logistics & incoterms','Cold-chain capable. Incoterms negotiated per order. Flexible delivery.'],
                        ['04','Distribution network','Tetouan pharmacy network + Sahel distribution partners activation.'],
                    ] as [$num,$title,$desc])
                    <div class="eb-step"><div class="eb-step-num">{{ $num }}</div><div><div class="eb-step-title">{{ $title }}</div><div class="eb-step-desc">{{ $desc }}</div></div></div>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="eb-eyebrow">Discuss your sourcing</div>
                <h2 class="eb-h2" style="margin-bottom:1.5rem;">Tell us what you need</h2>
                <div class="eb-form-group"><label class="eb-form-label">Company *</label><input class="eb-form-input" type="text"></div>
                <div class="eb-form-group"><label class="eb-form-label">Email *</label><input class="eb-form-input" type="email"></div>
                <div class="eb-form-group"><label class="eb-form-label">Target market *</label>
                    <select class="eb-form-select"><option>Morocco</option><option>Sahel</option><option>Europe</option><option>Other</option></select>
                </div>
                <div class="eb-form-group"><label class="eb-form-label">Product category *</label>
                    <select class="eb-form-select"><option>Human supplements</option><option>Veterinary products</option><option>Parapharmacy</option><option>Mixed</option></select>
                </div>
                <div class="eb-form-group"><label class="eb-form-label">Message</label><textarea class="eb-form-textarea" placeholder="Describe your sourcing need or product type..."></textarea></div>
                <button class="eb-btn eb-btn-primary" style="width:100%;">Discuss sourcing →</button>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : WEBKERNEL
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'webkernel' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/services/logiciels-webkernel</strong> &nbsp;·&nbsp; Blocks : <strong>HeroBlock · FeatureGridBlock · PricingTableBlock · QuoteFormBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link active" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Request a demo →</button></div></nav>

    <div class="eb-hero-bg" style="padding:5rem 2rem 4rem;">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;">
            <div>
                <div class="eb-eyebrow" style="color:var(--eb-green);">Official Webkernel Reseller — Spain & Europe</div>
                <h1 class="eb-h1" style="color:#fff;margin-bottom:1rem;">Webkernel Software<br><span style="color:var(--eb-green);">Solutions</span></h1>
                <p class="eb-lead" style="color:rgba(255,255,255,.65);margin-bottom:1.5rem;">CRM, ERP, Medtech and SaaS platforms built on the open-source Webkernel kernel. Own your code. Own your data. No vendor lock-in.</p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem;">
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">Open Source Core</span>
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">Perpetual licence</span>
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">Self-hosted</span>
                    <span class="eb-badge" style="background:rgba(34,197,94,.15);color:var(--eb-green);border-color:rgba(34,197,94,.3);">GDPR compliant</span>
                </div>
                <button class="eb-btn eb-btn-primary eb-btn-lg">Request a demo →</button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                @foreach([
                    ['🗂','CRM / ERP','Custom pipeline, stock, sales, operations'],
                    ['🏥','Medtech Platform','Patient records, scheduling, compliance'],
                    ['⚡','SaaS Builder','Multi-tenant, billing, API, user management'],
                    ['👥','HR Manager','Staff, payroll, attendance, documents'],
                ] as [$icon,$title,$desc])
                <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:var(--eb-radius-lg);padding:1.25rem;">
                    <div style="font-size:1.3rem;margin-bottom:.5rem;">{{ $icon }}</div>
                    <div style="font-weight:600;color:#fff;font-size:.88rem;margin-bottom:.25rem;">{{ $title }}</div>
                    <div style="font-size:.76rem;color:rgba(255,255,255,.45);">{{ $desc }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container">
            <div style="text-align:center;margin-bottom:3rem;">
                <div class="eb-eyebrow" style="justify-content:center;">Licensing model</div>
                <h2 class="eb-h2">Simple, transparent, perpetual.</h2>
                <p class="eb-lead" style="margin:1rem auto 0;text-align:center;">Buy once. Deploy anywhere. Own everything.</p>
            </div>
            <div class="eb-grid-3">
                <div class="eb-pricing-card">
                    <div style="font-weight:700;font-family:'Syne',sans-serif;margin-bottom:.5rem;">Core Kernel</div>
                    <div class="eb-pricing-price">Free</div>
                    <div class="eb-pricing-period" style="margin-bottom:1.25rem;">Eclipse Public License v2.0</div>
                    @foreach(['Self-hosted deployment','Full source code access','Community support','Laravel 11 foundation'] as $f)
                    <div class="eb-pricing-feature">{{ $f }}</div>
                    @endforeach
                    <button class="eb-btn eb-btn-outline" style="width:100%;margin-top:1.5rem;">Get started free</button>
                </div>
                <div class="eb-pricing-card featured">
                    <div style="font-weight:700;font-family:'Syne',sans-serif;margin-bottom:.5rem;">Business Modules</div>
                    <div class="eb-pricing-price">On quote</div>
                    <div class="eb-pricing-period" style="margin-bottom:1.25rem;">Perpetual licence · updates capped 25%/yr</div>
                    @foreach(['CRM / ERP / HR / Medtech','Perpetual usage right','12-month functional guarantee','Priority support from Numerimondes'] as $f)
                    <div class="eb-pricing-feature">{{ $f }}</div>
                    @endforeach
                    <button class="eb-btn eb-btn-primary" style="width:100%;margin-top:1.5rem;">Request a demo →</button>
                </div>
                <div class="eb-pricing-card">
                    <div style="font-weight:700;font-family:'Syne',sans-serif;margin-bottom:.5rem;">Sovereign / Institutional</div>
                    <div class="eb-pricing-price">Custom</div>
                    <div class="eb-pricing-period" style="margin-bottom:1.25rem;">Government & critical infrastructure</div>
                    @foreach(['Air-gapped / offline deployment','Sealed configurations','Local verification mode','ICC arbitration (Casablanca)'] as $f)
                    <div class="eb-pricing-feature">{{ $f }}</div>
                    @endforeach
                    <button class="eb-btn eb-btn-outline" style="width:100%;margin-top:1.5rem;">Contact us</button>
                </div>
            </div>
            <p style="text-align:center;font-size:.8rem;color:var(--eb-muted);margin-top:1.5rem;">Full licence terms: <a href="https://www.numerimondes.com/webkernel/licence" style="color:var(--eb-green-dark);">numerimondes.com/webkernel/licence</a></p>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : TRAVEL
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'travel' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/services/travel-conciergerie</strong> &nbsp;·&nbsp; Blocks : <strong>HeroBlock · FeatureGridBlock · QuoteFormBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link active" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Contact us →</button></div></nav>

    <div style="background:var(--eb-surface);padding:5rem 2rem 4rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;">
            <div>
                <div class="eb-eyebrow">Mobility · Corporate</div>
                <h1 class="eb-h1" style="margin-bottom:1rem;">Travel & Concierge</h1>
                <p class="eb-lead" style="margin-bottom:2rem;">End-to-end business travel management and concierge services for organisations and executives. 24/7 support. No complexity for your team.</p>
                <button class="eb-btn eb-btn-primary eb-btn-lg">Contact our team →</button>
            </div>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                @foreach([
                    ['✈️','Flight & accommodation booking','24/7 availability. Best rates. Full trip management.'],
                    ['📋','Corporate travel policy management','Your policy enforced. Expense tracking. Reporting.'],
                    ['🚨','Emergency travel support','< 1h response. Rebooking, cancellations, emergencies.'],
                    ['🎪','Event & delegation logistics','Medical delegations, trade shows, institutional visits.'],
                ] as [$icon,$title,$desc])
                <div class="eb-feature-row">
                    <div class="eb-feature-icon">{{ $icon }}</div>
                    <div><div class="eb-feature-title">{{ $title }}</div><div class="eb-feature-desc">{{ $desc }}</div></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : ABOUT
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'about' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/about</strong> &nbsp;·&nbsp; Blocks : <strong>HeroBlock · TextImageBlock · TeamCardBlock · ProcessStepsBlock · PartnerLogosBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link" href="#">Services</a><a class="eb-nav-link active" href="#">About</a><a class="eb-nav-link" href="#">Blog</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button></div></nav>

    <div style="background:var(--eb-surface);padding:5rem 2rem 4rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;">
            <div>
                <div class="eb-eyebrow">Who we are</div>
                <h1 class="eb-h1" style="margin-bottom:1rem;">Who is EASBridge?</h1>
                <p class="eb-lead" style="margin-bottom:1.5rem;">A Barcelona-based operational partner for healthcare, hospitality and digital organisations across Spain and Europe — built for agility, dedicated to results.</p>
                <p style="color:var(--eb-muted);font-size:.9rem;line-height:1.75;margin-bottom:2rem;">EASBridge is a structure entirely focused on managing the operational environment of its clients. From hospital sterilisation to software distribution, travel management to pharma sourcing — we bring together the right expertise, the right partners, and the right commitment to deliver where large groups cannot.</p>
                <div style="display:flex;gap:1rem;">
                    <div style="text-align:center;padding:1rem;background:#fff;border:1px solid var(--eb-border);border-radius:var(--eb-radius-lg);min-width:90px;">
                        <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:var(--eb-green);">2026</div>
                        <div style="font-size:.72rem;color:var(--eb-muted);">Founded</div>
                    </div>
                    <div style="text-align:center;padding:1rem;background:#fff;border:1px solid var(--eb-border);border-radius:var(--eb-radius-lg);min-width:90px;">
                        <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:var(--eb-green);">5</div>
                        <div style="font-size:.72rem;color:var(--eb-muted);">Pillars</div>
                    </div>
                    <div style="text-align:center;padding:1rem;background:#fff;border:1px solid var(--eb-border);border-radius:var(--eb-radius-lg);min-width:90px;">
                        <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:var(--eb-green);">ES+MA</div>
                        <div style="font-size:.72rem;color:var(--eb-muted);">Markets</div>
                    </div>
                </div>
            </div>
            <div class="eb-photo-placeholder" style="min-height:380px;">
                <div style="text-align:center;">
                    <div style="font-size:3rem;margin-bottom:.5rem;">📸</div>
                    <div>Photo — Taha Laamrani</div>
                    <div style="font-size:.75rem;margin-top:.25rem;">À ajouter</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Team --}}
    <div class="eb-section">
        <div class="eb-container">
            <div class="eb-eyebrow">Leadership</div>
            <h2 class="eb-h2" style="margin-bottom:2rem;">The team behind EASBridge</h2>
            <div class="eb-grid-2" style="max-width:800px;">
                <div class="eb-card">
                    <div style="display:flex;gap:1rem;align-items:flex-start;">
                        <div style="width:56px;height:56px;border-radius:50%;background:var(--eb-green-pale);border:2px solid var(--eb-green);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">👤</div>
                        <div>
                            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--eb-navy);">Taha Laamrani</div>
                            <div style="font-size:.8rem;color:var(--eb-green-dark);font-weight:500;margin:.15rem 0;">Founder & Managing Director</div>
                            <div style="font-size:.78rem;color:var(--eb-muted);">Barcelona, España · NIE Z3275315M</div>
                        </div>
                    </div>
                    <p style="font-size:.84rem;color:var(--eb-muted);margin-top:1rem;line-height:1.7;">Pharmacist with deep expertise in healthcare, pharma sourcing and operational management. Leads all commercial relationships and market execution across Spain, Morocco and Europe.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.75rem;">
                        @foreach(['Healthcare','Pharmacie','Trading','Sourcing'] as $tag)
                        <span class="eb-tag">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="eb-card" style="border-color:#bfdbfe;background:var(--eb-blue-light);">
                    <div style="display:flex;gap:1rem;align-items:flex-start;">
                        <div style="width:56px;height:56px;border-radius:50%;background:#dbeafe;border:2px solid var(--eb-blue-mid);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">🔷</div>
                        <div>
                            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--eb-navy);">Numerimondes</div>
                            <div style="font-size:.8rem;color:var(--eb-blue);font-weight:500;margin:.15rem 0;">Technology Partner — Accord 19CQRCFDP</div>
                            <div style="font-size:.78rem;color:var(--eb-muted);">Casablanca, Maroc · RC 152844</div>
                        </div>
                    </div>
                    <p style="font-size:.84rem;color:var(--eb-slate);margin-top:1rem;line-height:1.7;">Technology engineering, public tender assembly and Webkernel development. Provides technical backbone, AO filing expertise and sovereign software infrastructure for all EASBridge operations.</p>
                    <a href="https://numerimondes.com" style="font-size:.8rem;color:var(--eb-blue-mid);font-weight:600;display:flex;align-items:center;gap:.3rem;margin-top:.75rem;">numerimondes.com ›</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Roadmap --}}
    <div style="background:var(--eb-navy);padding:4rem 2rem;">
        <div class="eb-container">
            <div class="eb-eyebrow" style="color:var(--eb-green);">Development roadmap</div>
            <h2 class="eb-h2" style="color:#fff;margin-bottom:2.5rem;">Three phases of growth</h2>
            <div class="eb-grid-3">
                @foreach([
                    ['2026','Phase 1 — Agility','Autonomo structure · Focus on hospital sterilisation Spain · Maximum flexibility · First contracts','active'],
                    ['2027','Phase 2 — Divisions','Commercial sub-divisions · Sector credibility · Multi-contract references across Spain',''],
                    ['2028','Phase 3 — Empresa','Transformation to Spanish SL (SARL) · Multi-country references · Venture-ready structure',''],
                ] as [$year,$title,$desc,$state])
                <div style="background:rgba(255,255,255,.05);border:1px solid {{ $state ? 'var(--eb-green)' : 'rgba(255,255,255,.1)' }};border-radius:var(--eb-radius-lg);padding:1.75rem;position:relative;">
                    @if($state)
                    <div class="eb-badge eb-badge-green" style="position:absolute;top:1rem;right:1rem;">Current</div>
                    @endif
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:var(--eb-green);margin-bottom:.35rem;">{{ $year }}</div>
                    <div style="font-weight:600;color:#fff;margin-bottom:.5rem;">{{ $title }}</div>
                    <div style="font-size:.82rem;color:rgba(255,255,255,.5);line-height:1.6;">{{ $desc }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : BLOG
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'blog' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/blog</strong> &nbsp;·&nbsp; Block : <strong>BlogGridBlock · BlogPostBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link active" href="#">Blog</a><a class="eb-nav-link" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span><button class="eb-btn eb-btn-primary eb-btn-sm">Get a quote →</button></div></nav>

    <div style="background:var(--eb-surface);padding:4rem 2rem 3rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container">
            <div class="eb-eyebrow">Insights & News</div>
            <h1 class="eb-h1" style="margin-bottom:.5rem;">Blog</h1>
            <p class="eb-lead">Market updates, tender wins, Webkernel releases and industry news — EN & ES.</p>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem;">
                @foreach(['All','Healthcare','Software','Tenders','Sourcing'] as $cat)
                <button class="eb-btn eb-btn-sm {{ $cat === 'All' ? 'eb-btn-primary' : 'eb-btn-outline' }}" style="padding:.35rem .85rem;font-size:.78rem;">{{ $cat }}</button>
                @endforeach
            </div>

            {{-- Featured article --}}
            <div style="background:#fff;border:1px solid var(--eb-border);border-radius:var(--eb-radius-lg);overflow:hidden;display:grid;grid-template-columns:1fr 1fr;margin-bottom:2rem;box-shadow:var(--eb-shadow);">
                <div style="background:linear-gradient(135deg,#0f172a,#1e293b);display:flex;align-items:center;justify-content:center;min-height:260px;padding:2rem;">
                    <div style="text-align:center;color:rgba(255,255,255,.5);">
                        <div style="font-size:3rem;">🏥</div>
                        <div style="font-size:.8rem;margin-top:.5rem;">Featured article image</div>
                    </div>
                </div>
                <div style="padding:2rem;">
                    <span class="eb-badge eb-badge-green" style="margin-bottom:1rem;">Healthcare</span>
                    <h2 class="eb-h3" style="margin-bottom:.75rem;font-size:1.15rem;">Hospital sterilisation procurement: what buyers look for in 2026</h2>
                    <p style="font-size:.84rem;color:var(--eb-muted);line-height:1.7;margin-bottom:1.25rem;">A breakdown of EN ISO compliance requirements and how to structure your technical bid to win sterilisation tenders in Spain and Europe.</p>
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:.75rem;color:var(--eb-muted);">April 2026 · 6 min read</span>
                        <button class="eb-btn eb-btn-outline eb-btn-sm">Read article →</button>
                    </div>
                </div>
            </div>

            <div class="eb-grid-3">
                @foreach([
                    ['🖥️','Software','Webkernel: sovereign software for institutions','Why open-source PHP infrastructure changes the vendor dependency equation for SMEs and institutions.','March 2026','5 min'],
                    ['📋','Tenders','LCSP for beginners: submitting your first Spanish public tender','Step-by-step guide to the Ley de Contratos del Sector Público — FNMT, PLACSP and sobre system explained.','March 2026','8 min'],
                    ['💊','Sourcing','ONSSA certification: exporting food supplements to Morocco','The complete process — from EU supplier selection to Moroccan market approval. What EASBridge manages for you.','February 2026','6 min'],
                ] as [$icon,$cat,$title,$excerpt,$date,$read])
                <div class="eb-card" style="display:flex;flex-direction:column;gap:.75rem;">
                    <div style="background:var(--eb-surface);border-radius:var(--eb-radius);height:140px;display:flex;align-items:center;justify-content:center;font-size:2rem;border:1px solid var(--eb-border);">{{ $icon }}</div>
                    <span class="eb-badge eb-badge-slate">{{ $cat }}</span>
                    <div class="eb-h3" style="font-size:.95rem;">{{ $title }}</div>
                    <div style="font-size:.82rem;color:var(--eb-muted);line-height:1.6;flex:1;">{{ $excerpt }}</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;">
                        <span style="font-size:.72rem;color:var(--eb-muted);">{{ $date }} · {{ $read }}</span>
                        <a style="font-size:.8rem;font-weight:600;color:var(--eb-green-dark);">Read →</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : CONTACT
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'contact' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/contact</strong> &nbsp;·&nbsp; Block : <strong>HeroBlock · QuoteFormBlock</strong></div>
    <nav class="eb-nav"><div class="eb-nav-logo"><div class="eb-logo-square">EB</div><span class="eb-nav-logo-text">EASBridge</span></div><div class="eb-nav-links"><a class="eb-nav-link" href="#">Home</a><a class="eb-nav-link" href="#">Services</a><a class="eb-nav-link" href="#">About</a><a class="eb-nav-link" href="#">Blog</a><a class="eb-nav-link active" href="#">Contact</a></div><div class="eb-nav-actions"><span class="eb-nav-lang">EN</span><span class="eb-nav-lang">ES</span></div></nav>

    <div style="background:var(--eb-surface);padding:4rem 2rem 3rem;border-bottom:1px solid var(--eb-border);">
        <div class="eb-container-sm" style="text-align:center;">
            <div class="eb-eyebrow" style="justify-content:center;">Get in touch</div>
            <h1 class="eb-h1" style="margin-bottom:.75rem;">Contact EASBridge</h1>
            <p class="eb-lead" style="margin:0 auto;">For quotes, partnerships, or general enquiries — we respond within 24 hours.</p>
        </div>
    </div>

    <div class="eb-section">
        <div class="eb-container" style="display:grid;grid-template-columns:1fr 1.6fr;gap:4rem;align-items:start;">
            <div>
                <h3 class="eb-h3" style="margin-bottom:1.25rem;">Direct contact</h3>
                @foreach([
                    ['📍','Address','C/ Ataulfo, 08002 Barcelona, España'],
                    ['📧','Email','contact@easbridge.com'],
                    ['🌐','Website','www.easbridge.com'],
                    ['🕐','Response time','Within 24 hours'],
                ] as [$icon,$label,$val])
                <div style="display:flex;gap:.75rem;align-items:flex-start;padding:.85rem 0;border-bottom:1px solid var(--eb-border);">
                    <span style="font-size:1.1rem;flex-shrink:0;margin-top:.1rem;">{{ $icon }}</span>
                    <div>
                        <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--eb-muted);">{{ $label }}</div>
                        <div style="font-size:.9rem;color:var(--eb-slate);margin-top:.15rem;">{{ $val }}</div>
                    </div>
                </div>
                @endforeach

                <div style="margin-top:1.5rem;">
                    <h3 class="eb-h3" style="margin-bottom:.75rem;">Our partners</h3>
                    <div style="display:flex;flex-direction:column;gap:.5rem;">
                        <a style="display:flex;align-items:center;gap:.5rem;font-size:.84rem;color:var(--eb-blue);font-weight:500;"><span>🔷</span> numerimondes.com</a>
                        <a style="display:flex;align-items:center;gap:.5rem;font-size:.84rem;color:var(--eb-green-dark);font-weight:500;"><span>🌐</span> webkernelphp.com</a>
                    </div>
                </div>

                <div class="eb-map-placeholder" style="margin-top:1.5rem;">
                    <div style="text-align:center;">
                        <div class="eb-map-pin">📍</div>
                        <div style="font-size:.82rem;margin-top:.25rem;font-weight:500;">Barcelona, España</div>
                        <div style="font-size:.72rem;margin-top:.1rem;">C/ Ataulfo, 08002</div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="eb-h3" style="margin-bottom:1.25rem;">Send us a message</h3>
                <div class="eb-grid-2">
                    <div class="eb-form-group"><label class="eb-form-label">Full name *</label><input class="eb-form-input" type="text" placeholder="Your name"></div>
                    <div class="eb-form-group"><label class="eb-form-label">Company *</label><input class="eb-form-input" type="text" placeholder="Organisation"></div>
                </div>
                <div class="eb-grid-2">
                    <div class="eb-form-group"><label class="eb-form-label">Country *</label>
                        <select class="eb-form-select"><option>Spain</option><option>Morocco</option><option>France</option><option>Other</option></select>
                    </div>
                    <div class="eb-form-group"><label class="eb-form-label">Phone</label><input class="eb-form-input" type="tel" placeholder="+34 ..."></div>
                </div>
                <div class="eb-form-group"><label class="eb-form-label">Email *</label><input class="eb-form-input" type="email" placeholder="your@company.com"></div>
                <div class="eb-form-group"><label class="eb-form-label">Subject *</label>
                    <select class="eb-form-select"><option>Request a quote</option><option>Partnership enquiry</option><option>Webkernel demo</option><option>General question</option></select>
                </div>
                <div class="eb-form-group"><label class="eb-form-label">Service concerned</label>
                    <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.4rem;">
                        @foreach(['Sterilisation','Cleaning','Supplements','Webkernel','Travel'] as $s)
                        <label style="display:flex;align-items:center;gap:.35rem;font-size:.82rem;cursor:pointer;background:var(--eb-surface);border:1px solid var(--eb-border);border-radius:var(--eb-radius);padding:.3rem .65rem;">
                            <input type="checkbox" style="accent-color:var(--eb-green);"> {{ $s }}
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="eb-form-group"><label class="eb-form-label">Message *</label><textarea class="eb-form-textarea" placeholder="Describe your need or project..."></textarea></div>
                <button class="eb-btn eb-btn-primary" style="width:100%;padding:.85rem;">Send message →</button>
                <p style="font-size:.72rem;color:var(--eb-muted);margin-top:.75rem;text-align:center;">We respond within 24 hours. No commitment required.</p>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : PARTNER LOGIN
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'login' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/partner/login</strong> &nbsp;·&nbsp; Accès : <strong>Restreint — 2 utilisateurs (Taha + Yassine)</strong> &nbsp;·&nbsp; Auth : <strong>Laravel Sanctum + Filament Shield</strong></div>

    <div style="min-height:100vh;background:var(--eb-navy);display:flex;align-items:center;justify-content:center;padding:2rem;">
        <div style="width:100%;max-width:420px;">
            <div style="text-align:center;margin-bottom:2rem;">
                <div style="display:inline-flex;align-items:center;gap:.65rem;margin-bottom:1.5rem;">
                    <div class="eb-logo-square" style="width:40px;height:40px;font-size:1rem;">EB</div>
                    <span style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;">EASBridge</span>
                </div>
                <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:#fff;margin-bottom:.35rem;">Partner Access</h1>
                <p style="font-size:.84rem;color:rgba(255,255,255,.45);">Restricted — Accord n° 19CQRCFDP</p>
            </div>

            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:var(--eb-radius-lg);padding:2rem;">
                <div class="eb-form-group">
                    <label class="eb-form-label" style="color:rgba(255,255,255,.6);">Email address</label>
                    <input class="eb-form-input" type="email" placeholder="partner@easbridge.com" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);color:#fff;">
                </div>
                <div class="eb-form-group">
                    <label class="eb-form-label" style="color:rgba(255,255,255,.6);">Password</label>
                    <input class="eb-form-input" type="password" placeholder="••••••••" style="background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);color:#fff;">
                </div>
                <button class="eb-btn eb-btn-primary" style="width:100%;padding:.85rem;" @click="page = 'dashboard'">Sign in →</button>
                <p style="font-size:.72rem;color:rgba(255,255,255,.3);text-align:center;margin-top:1rem;">Access restricted to authorised partners only.</p>
            </div>

            <div style="text-align:center;margin-top:1.5rem;">
                <a href="#" style="font-size:.8rem;color:rgba(255,255,255,.3);" @click.prevent="page = 'home'">← Back to easbridge.com</a>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : PARTNER DASHBOARD
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'dashboard' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/partner/dashboard</strong> &nbsp;·&nbsp; Rôles : <strong>partner_easbridge · partner_numerimondes</strong> &nbsp;·&nbsp; Module : <strong>easbridge-site</strong></div>

    <div class="eb-dash-wrap">
        <div class="eb-dash-nav">
            <div class="eb-logo-square" style="width:28px;height:28px;font-size:.7rem;">EB</div>
            <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;color:#fff;margin-right:.5rem;">EASBridge</span>
            <span style="color:rgba(255,255,255,.2);font-size:.8rem;margin-right:1rem;">/ Partner</span>
            <a class="eb-dash-nav-link active" @click="page = 'dashboard'" href="#">📊 Dashboard</a>
            <a class="eb-dash-nav-link" @click="page = 'tenders'" href="#">📋 Appels d'offres</a>
            <a class="eb-dash-nav-link" @click="page = 'commissions'" href="#">💶 Commissions</a>
            <a class="eb-dash-nav-link" @click="page = 'docs'" href="#">📁 Documents</a>
            <div style="margin-left:auto;display:flex;align-items:center;gap:.75rem;">
                <span style="font-size:.78rem;color:rgba(255,255,255,.4);">Taha Laamrani</span>
                <div style="width:28px;height:28px;border-radius:50%;background:var(--eb-green);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;">TL</div>
            </div>
        </div>

        <div class="eb-dash-body">
            <div style="margin-bottom:1.5rem;">
                <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:700;color:var(--eb-navy);">Dashboard</h1>
                <p style="font-size:.84rem;color:var(--eb-muted);">Accord n° 19CQRCFDP · Vue d'ensemble</p>
            </div>

            <div class="eb-grid-4" style="margin-bottom:1.5rem;">
                @foreach([
                    ['AO actifs','3','En cours de montage','📋','green'],
                    ['Commissions dues','8 400 €','Paiement sous 7j · Art. 3.5','💶','orange'],
                    ['Documents partagés','12','3 ajoutés ce mois','📁','blue'],
                    ['Prochaine échéance','15 mai','Stérilisation Marbella','📅','slate'],
                ] as [$label,$val,$sub,$icon,$color])
                <div class="eb-widget">
                    <div class="eb-widget-icon">{{ $icon }}</div>
                    <div class="eb-widget-label">{{ $label }}</div>
                    <div class="eb-widget-val" style="color:{{ $color === 'green' ? 'var(--eb-green-dark)' : ($color === 'orange' ? '#c2410c' : ($color === 'blue' ? 'var(--eb-blue)' : 'var(--eb-navy)')) }};">{{ $val }}</div>
                    <div class="eb-widget-sub">{{ $sub }}</div>
                </div>
                @endforeach
            </div>

            <div class="eb-grid-2">
                <div class="eb-widget">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3 class="eb-h3" style="font-size:.9rem;">Appels d'offres récents</h3>
                        <a style="font-size:.78rem;color:var(--eb-green-dark);font-weight:600;cursor:pointer;" @click="page = 'tenders'">Voir tous →</a>
                    </div>
                    @foreach([
                        ['AO-ES-001','Stérilisation Marbella','Montage','15 mai'],
                        ['AO-ES-002','Nettoyage Club Med','Veille','—'],
                        ['AO-ES-003','Logiciel RH Mairie','Identifié','30 juin'],
                    ] as [$ref,$title,$status,$date])
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.65rem 0;border-bottom:1px solid var(--eb-border);">
                        <div style="font-family:'Plus Jakarta Sans',monospace;font-size:.7rem;background:var(--eb-surface);padding:.2rem .45rem;border-radius:4px;color:var(--eb-muted);flex-shrink:0;">{{ $ref }}</div>
                        <div style="flex:1;font-size:.84rem;font-weight:500;color:var(--eb-navy);">{{ $title }}</div>
                        <span class="eb-status {{ $status === 'Montage' ? 'eb-status-active' : 'eb-status-draft' }}">{{ $status }}</span>
                        <span style="font-size:.75rem;color:var(--eb-muted);flex-shrink:0;">{{ $date }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="eb-widget">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                        <h3 class="eb-h3" style="font-size:.9rem;">Documents récents</h3>
                        <a style="font-size:.78rem;color:var(--eb-green-dark);font-weight:600;cursor:pointer;" @click="page = 'docs'">Voir tous →</a>
                    </div>
                    @foreach([
                        ['📄','Mémoire technique stérilisation v2','Numerimondes','01 avr.'],
                        ['📊','Analyse AO Andalousie Q2 2026','Numerimondes','28 mars'],
                        ['📋','Check-list FNMT + documents LCSP','EASBridge','25 mars'],
                    ] as [$icon,$name,$by,$date])
                    <div style="display:flex;align-items:center;gap:.75rem;padding:.65rem 0;border-bottom:1px solid var(--eb-border);">
                        <span style="font-size:1.2rem;">{{ $icon }}</span>
                        <div style="flex:1;">
                            <div style="font-size:.82rem;font-weight:500;color:var(--eb-navy);">{{ $name }}</div>
                            <div style="font-size:.72rem;color:var(--eb-muted);">{{ $by }} · {{ $date }}</div>
                        </div>
                        <button class="eb-btn eb-btn-outline eb-btn-sm" style="font-size:.7rem;padding:.25rem .6rem;">↓</button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : TENDERS
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'tenders' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/partner/tenders</strong> &nbsp;·&nbsp; Resource Filament : <strong>TenderResource</strong> &nbsp;·&nbsp; Table : <strong>eb_tenders</strong></div>
    <div class="eb-dash-wrap">
        <div class="eb-dash-nav">
            <div class="eb-logo-square" style="width:28px;height:28px;font-size:.7rem;">EB</div>
            <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;color:#fff;margin-right:1rem;">EASBridge / Partner</span>
            <a class="eb-dash-nav-link" @click="page = 'dashboard'" href="#">📊 Dashboard</a>
            <a class="eb-dash-nav-link active" @click="page = 'tenders'" href="#">📋 Appels d'offres</a>
            <a class="eb-dash-nav-link" @click="page = 'commissions'" href="#">💶 Commissions</a>
            <a class="eb-dash-nav-link" @click="page = 'docs'" href="#">📁 Documents</a>
        </div>
        <div class="eb-dash-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <div><h1 style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:var(--eb-navy);">Appels d'offres</h1>
                <p style="font-size:.82rem;color:var(--eb-muted);">Suivi complet — veille → montage → dépôt → résultat</p></div>
                <button class="eb-btn eb-btn-primary eb-btn-sm">+ Nouvel AO</button>
            </div>

            <div style="display:flex;gap:.4rem;margin-bottom:1.25rem;flex-wrap:wrap;">
                @foreach(['Tous (5)','Veille (1)','Montage (2)','Déposé (1)','Remporté (1)'] as $filter)
                <button class="eb-btn eb-btn-sm {{ str_contains($filter,'Tous') ? 'eb-btn-primary' : 'eb-btn-outline' }}" style="font-size:.75rem;padding:.3rem .7rem;">{{ $filter }}</button>
                @endforeach
            </div>

            <div class="eb-widget" style="padding:0;overflow:hidden;">
                <table class="eb-table">
                    <thead><tr>
                        <th>Référence</th><th>Titre</th><th>Secteur</th><th>Statut</th><th>Montant net</th><th>Commission</th><th>Échéance</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                        @foreach([
                            ['AO-ES-001','Stérilisation Hôpital Marbella','Santé','Montage','—','20 %','15 mai 2026','active'],
                            ['AO-ES-002','Nettoyage Club Med Andalousie','Hôtellerie','Montage','—','20 %','30 juin 2026','active'],
                            ['AO-ES-003','Logiciel RH — Mairie Barcelone','Logiciel','Veille','—','20 %','—','draft'],
                            ['AO-ES-004','Stérilisation Clinique Privée BCN','Santé','Déposé','—','20 %','10 avr. 2026','pending'],
                            ['AO-ES-000','Désinfection Cabinet Dentaire Test','Santé','Remporté','42 000 €','20 %','Clôturé','success'],
                        ] as [$ref,$title,$sector,$status,$net,$rate,$date,$type])
                        <tr>
                            <td><span style="font-family:monospace;font-size:.75rem;background:var(--eb-surface);padding:.15rem .4rem;border-radius:4px;">{{ $ref }}</span></td>
                            <td style="font-weight:500;max-width:200px;">{{ $title }}</td>
                            <td><span class="eb-badge eb-badge-slate">{{ $sector }}</span></td>
                            <td><span class="eb-status {{ $type === 'active' ? 'eb-status-active' : ($type === 'success' ? 'eb-status-active' : ($type === 'pending' ? 'eb-status-pending' : 'eb-status-draft')) }}" style="{{ $type === 'success' ? 'background:#f0fdf4;color:#15803d;' : '' }}">{{ $status }}</span></td>
                            <td style="font-weight:{{ $net !== '—' ? '600' : '400' }};">{{ $net }}</td>
                            <td>{{ $rate }}</td>
                            <td style="font-size:.8rem;color:var(--eb-muted);">{{ $date }}</td>
                            <td><button class="eb-btn eb-btn-outline eb-btn-sm" style="font-size:.72rem;padding:.25rem .55rem;">Voir</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : COMMISSIONS
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'commissions' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/partner/commissions</strong> &nbsp;·&nbsp; Resource : <strong>CommissionResource</strong> &nbsp;·&nbsp; Value Object : <strong>CommissionCalculator</strong> (immuable)</div>
    <div class="eb-dash-wrap">
        <div class="eb-dash-nav">
            <div class="eb-logo-square" style="width:28px;height:28px;font-size:.7rem;">EB</div>
            <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;color:#fff;margin-right:1rem;">EASBridge / Partner</span>
            <a class="eb-dash-nav-link" @click="page = 'dashboard'" href="#">📊 Dashboard</a>
            <a class="eb-dash-nav-link" @click="page = 'tenders'" href="#">📋 Appels d'offres</a>
            <a class="eb-dash-nav-link active" @click="page = 'commissions'" href="#">💶 Commissions</a>
            <a class="eb-dash-nav-link" @click="page = 'docs'" href="#">📁 Documents</a>
        </div>
        <div class="eb-dash-body">
            <h1 style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:var(--eb-navy);margin-bottom:.35rem;">Commissions</h1>
            <p style="font-size:.82rem;color:var(--eb-muted);margin-bottom:1.5rem;">Formule : <code style="background:var(--eb-surface);padding:.1rem .4rem;border-radius:4px;font-size:.78rem;">net = CA_brut − coûts_directs_justifiés</code> · Paiement sous 7j après réception facture (Art. 3.5)</p>

            <div class="eb-grid-3" style="margin-bottom:1.5rem;">
                <div class="eb-widget" style="border-left:3px solid var(--eb-green);">
                    <div class="eb-widget-label">Total commissions dues</div>
                    <div class="eb-widget-val" style="color:var(--eb-green-dark);">8 400 €</div>
                    <div class="eb-widget-sub">EASBridge → Numerimondes</div>
                </div>
                <div class="eb-widget" style="border-left:3px solid var(--eb-blue);">
                    <div class="eb-widget-label">Commissions perçues</div>
                    <div class="eb-widget-val" style="color:var(--eb-blue);">0 €</div>
                    <div class="eb-widget-sub">Numerimondes → EASBridge</div>
                </div>
                <div class="eb-widget" style="border-left:3px solid #c2410c;">
                    <div class="eb-widget-label">En retard (> 7j)</div>
                    <div class="eb-widget-val" style="color:#c2410c;">0 €</div>
                    <div class="eb-widget-sub">Aucun retard de paiement</div>
                </div>
            </div>

            <div class="eb-widget" style="padding:0;overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--eb-border);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:600;font-size:.9rem;">Détail des opérations</span>
                    <button class="eb-btn eb-btn-outline eb-btn-sm">↓ Exporter CSV</button>
                </div>
                <table class="eb-table">
                    <thead><tr>
                        <th>Opération</th><th>Type</th><th>CA brut</th><th>Coûts directs</th><th>Net</th><th>Taux</th><th>Commission</th><th>Statut</th><th>Facture</th>
                    </tr></thead>
                    <tbody>
                        <tr>
                            <td style="font-weight:500;">AO Stérilisation BCN</td>
                            <td><span class="eb-badge eb-badge-slate">AO remporté</span></td>
                            <td>48 000 €</td>
                            <td>6 000 €</td>
                            <td style="font-weight:600;">42 000 €</td>
                            <td>20 %</td>
                            <td style="font-weight:700;color:var(--eb-green-dark);">8 400 €</td>
                            <td><span class="eb-status eb-status-pending">En attente</span></td>
                            <td><button class="eb-btn eb-btn-primary eb-btn-sm" style="font-size:.72rem;padding:.25rem .6rem;">Émettre</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : DOCUMENTS
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'docs' && 'active'">
    <div class="eb-cdc-info-strip">Route : <strong>/partner/documents</strong> &nbsp;·&nbsp; Resource : <strong>PartnerDocumentResource</strong> &nbsp;·&nbsp; Storage : <strong>Spatie Media Library</strong></div>
    <div class="eb-dash-wrap">
        <div class="eb-dash-nav">
            <div class="eb-logo-square" style="width:28px;height:28px;font-size:.7rem;">EB</div>
            <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;color:#fff;margin-right:1rem;">EASBridge / Partner</span>
            <a class="eb-dash-nav-link" @click="page = 'dashboard'" href="#">📊 Dashboard</a>
            <a class="eb-dash-nav-link" @click="page = 'tenders'" href="#">📋 Appels d'offres</a>
            <a class="eb-dash-nav-link" @click="page = 'commissions'" href="#">💶 Commissions</a>
            <a class="eb-dash-nav-link active" @click="page = 'docs'" href="#">📁 Documents</a>
        </div>
        <div class="eb-dash-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <div><h1 style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700;color:var(--eb-navy);">Documents partagés</h1>
                <p style="font-size:.82rem;color:var(--eb-muted);">Mémoires techniques, analyses AO, documents administratifs</p></div>
                <button class="eb-btn eb-btn-primary eb-btn-sm">+ Déposer un document</button>
            </div>

            <div style="display:flex;gap:.4rem;margin-bottom:1.25rem;flex-wrap:wrap;">
                @foreach(['Tous (12)','AO (5)','Administratif (4)','Technique (2)','Divers (1)'] as $f)
                <button class="eb-btn eb-btn-sm {{ str_contains($f,'Tous') ? 'eb-btn-primary' : 'eb-btn-outline' }}" style="font-size:.75rem;padding:.3rem .7rem;">{{ $f }}</button>
                @endforeach
            </div>

            <div class="eb-widget" style="padding:0;overflow:hidden;">
                <table class="eb-table">
                    <thead><tr><th>Document</th><th>Catégorie</th><th>AO lié</th><th>Déposé par</th><th>Date</th><th>Taille</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach([
                            ['📄','Mémoire_technique_stérilisation_v2.pdf','AO','AO-ES-001','Numerimondes','01 avr. 2026','2.4 MB'],
                            ['📊','Analyse_AO_Andalousie_Q2_2026.xlsx','AO','AO-ES-002','Numerimondes','28 mars 2026','1.1 MB'],
                            ['📋','Check-list_FNMT_LCSP.pdf','Administratif','—','EASBridge','25 mars 2026','340 KB'],
                            ['📄','Accord_19CQRCFDP_signé.pdf','Administratif','—','Numerimondes','20 mars 2026','1.8 MB'],
                            ['📝','Brief_nettoyage_Club_Med.docx','Technique','AO-ES-002','EASBridge','15 mars 2026','250 KB'],
                        ] as [$icon,$name,$cat,$ao,$by,$date,$size])
                        <tr>
                            <td style="display:flex;align-items:center;gap:.5rem;">{{ $icon }}<span style="font-size:.82rem;font-weight:500;">{{ $name }}</span></td>
                            <td><span class="eb-badge eb-badge-slate">{{ $cat }}</span></td>
                            <td style="font-size:.8rem;color:var(--eb-muted);">{{ $ao }}</td>
                            <td style="font-size:.8rem;">{{ $by }}</td>
                            <td style="font-size:.78rem;color:var(--eb-muted);">{{ $date }}</td>
                            <td style="font-size:.75rem;color:var(--eb-muted);">{{ $size }}</td>
                            <td style="display:flex;gap:.3rem;">
                                <button class="eb-btn eb-btn-outline eb-btn-sm" style="font-size:.7rem;padding:.2rem .5rem;">↓ DL</button>
                                <button class="eb-btn eb-btn-sm" style="font-size:.7rem;padding:.2rem .5rem;background:var(--eb-surface);border:1px solid var(--eb-border);color:var(--eb-muted);">🗑</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     PAGE : SITEMAP INTERACTIF
════════════════════════════════════════════════════════════ --}}
<div class="eb-page-panel" :class="page === 'sitemap' && 'active'">
    <div class="eb-cdc-info-strip">Architecture : <strong>Module unique easbridge-site</strong> · 11 routes publiques + 4 routes privées · 15 blocks WebKernel · 3 Filament Resources</div>

    <div style="padding:3rem 2rem;background:var(--eb-surface);min-height:100vh;">
        <div class="eb-container">
            <div class="eb-eyebrow">Architecture du module easbridge-site</div>
            <h1 class="eb-h1" style="margin-bottom:.5rem;">Sitemap interactif</h1>
            <p class="eb-lead" style="margin-bottom:2.5rem;">Cliquez sur une page pour la visualiser.</p>

            {{-- Légende --}}
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;">
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--eb-muted);"><div style="width:16px;height:16px;background:var(--eb-navy);border-radius:3px;"></div> Root</div>
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--eb-muted);"><div style="width:16px;height:16px;background:#fff;border:1.5px solid var(--eb-border);border-radius:3px;"></div> Page publique</div>
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--eb-muted);"><div style="width:16px;height:16px;background:#bbf7d0;border:1.5px solid #16a34a;border-radius:3px;"></div> Sous-page service</div>
                <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--eb-muted);"><div style="width:16px;height:16px;background:var(--eb-blue-light);border:1.5px solid #93c5fd;border-radius:3px;"></div> Espace partenaire (privé)</div>
            </div>

            {{-- Root --}}
            <div style="display:flex;flex-direction:column;align-items:center;gap:0;">
                <div class="eb-sitemap-node root" style="padding:.65rem 2rem;cursor:pointer;" @click="page = 'home'">🌐 www.easbridge.com</div>

                {{-- Level 1 connector --}}
                <div style="width:2px;height:32px;background:var(--eb-border);"></div>

                {{-- Level 1 nodes --}}
                <div style="display:flex;align-items:flex-start;gap:0;width:100%;justify-content:center;position:relative;">
                    {{-- Horizontal line --}}
                    <div style="position:absolute;top:0;left:10%;right:10%;height:2px;background:var(--eb-border);"></div>

                    @foreach([
                        ['Home','/','home',''],
                        ['Services','/services','services',''],
                        ['À propos','/about','about',''],
                        ['Blog','/blog','blog',''],
                        ['Contact','/contact','contact',''],
                        ['🔐 Partner','/partner','login','private'],
                    ] as [$label,$route,$pg,$type])
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;padding:0 .25rem;">
                        <div style="width:2px;height:32px;background:var(--eb-border);"></div>
                        <div class="eb-sitemap-node {{ $type === 'private' ? 'private' : '' }}" @click="page = '{{ $pg }}'" style="cursor:pointer;font-size:.78rem;padding:.45rem .75rem;white-space:nowrap;">
                            {{ $label }}
                            <div style="font-size:.62rem;color:{{ $type === 'private' ? 'var(--eb-blue)' : 'var(--eb-muted)' }};margin-top:.15rem;">{{ $route }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Level 2 : Services sub-pages --}}
                <div style="width:100%;margin-top:1.5rem;padding:0 0 0 0;display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;max-width:900px;margin-left:auto;margin-right:auto;">
                    <div style="grid-column:1/-1;font-size:.7rem;color:var(--eb-muted);text-align:center;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.25rem;">Sous-pages /services/</div>
                    @foreach([
                        ['🏥 Stérilisation','/services/sterilisation','steri'],
                        ['✨ Nettoyage','/services/nettoyage','nettoyage'],
                        ['💊 Compléments','/services/complements-alimentaires','complements'],
                        ['🖥 Webkernel','/services/logiciels-webkernel','webkernel'],
                        ['✈️ Travel','/services/travel-conciergerie','travel'],
                    ] as [$label,$route,$pg])
                    <div class="eb-sitemap-node service" @click="page = '{{ $pg }}'" style="cursor:pointer;font-size:.75rem;padding:.45rem .5rem;">
                        {{ $label }}
                        <div style="font-size:.6rem;color:var(--eb-green-dark);margin-top:.1rem;">{{ $route }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Level 2 : Partner sub-pages --}}
                <div style="width:100%;margin-top:1rem;display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;max-width:720px;margin-left:auto;margin-right:auto;">
                    <div style="grid-column:1/-1;font-size:.7rem;color:rgba(30,58,138,.6);text-align:center;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.25rem;">Espace partenaire — /partner/</div>
                    @foreach([
                        ['🔐 Login','/partner/login','login'],
                        ['📊 Dashboard','/partner/dashboard','dashboard'],
                        ['📋 AO','/partner/tenders','tenders'],
                        ['💶 Commissions','/partner/commissions','commissions'],
                    ] as [$label,$route,$pg])
                    <div class="eb-sitemap-node private" @click="page = '{{ $pg }}'" style="cursor:pointer;font-size:.75rem;padding:.45rem .5rem;">
                        {{ $label }}
                        <div style="font-size:.6rem;color:var(--eb-blue);margin-top:.1rem;">{{ $route }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <hr class="eb-divider" style="margin:3rem 0;">

            {{-- Module summary --}}
            <div class="eb-grid-3" style="gap:1rem;">
                <div class="eb-card">
                    <div style="font-size:1.5rem;margin-bottom:.5rem;">🧩</div>
                    <div class="eb-h3" style="margin-bottom:.5rem;">Module unique</div>
                    <div style="font-family:monospace;font-size:.8rem;background:var(--eb-surface);padding:.4rem .75rem;border-radius:var(--eb-radius);color:var(--eb-slate);margin-bottom:.5rem;">easbridge-site</div>
                    <div style="font-size:.82rem;color:var(--eb-muted);">Un seul ServiceProvider. Un seul dépôt git privé. Toutes les routes, blocks et resources dans un module WebKernel unifié.</div>
                </div>
                <div class="eb-card">
                    <div style="font-size:1.5rem;margin-bottom:.5rem;">⚙️</div>
                    <div class="eb-h3" style="margin-bottom:.5rem;">15 Blocks WebKernel</div>
                    <div style="font-size:.82rem;color:var(--eb-muted);line-height:1.7;">HeroBlock · TrustBarBlock · ServiceGridBlock · FeatureGridBlock · StatsRowBlock · TextImageBlock · CtaBannerBlock · QuoteFormBlock · BlogGridBlock · BlogPostBlock · TeamCardBlock · PartnerLogosBlock · ProcessStepsBlock · PricingTableBlock · ServiceDetailBlock</div>
                </div>
                <div class="eb-card">
                    <div style="font-size:1.5rem;margin-bottom:.5rem;">🔐</div>
                    <div class="eb-h3" style="margin-bottom:.5rem;">3 Filament Resources</div>
                    <div style="font-size:.82rem;color:var(--eb-muted);line-height:1.8;">
                        <div><strong>TenderResource</strong> — eb_tenders</div>
                        <div><strong>CommissionResource</strong> — eb_commissions</div>
                        <div><strong>PartnerDocumentResource</strong> — Spatie Media</div>
                        <div style="margin-top:.5rem;">Rôles : <code style="font-size:.75em;background:var(--eb-surface);padding:.1rem .3rem;border-radius:3px;">partner_easbridge</code> · <code style="font-size:.75em;background:var(--eb-surface);padding:.1rem .3rem;border-radius:3px;">partner_numerimondes</code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>{{-- /.eb-cdc-main --}}
</div>{{-- /.eb-cdc-shell --}}
</div>{{-- /.eb-proto --}}
