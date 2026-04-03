{{--
    CDC — EASBridge
    Generated : 02 avril 2026 | Mode : B — WebKernel (module unique easbridge-site) | v1.1
    Accord partenariat n° 19CQRCFDP — Numerimondes × EASBridge
    Document confidentiel — usage strictement interne
    ─────────────────────────────────────────────────────────────────
    Usage : blade partiel, à inclure dans une page WebKernel.
    Dépendances attendues dans le layout parent :
      - @filamentStyles / @filamentScripts
      - Alpine.js (cdn.jsdelivr.net)
      - Tailwind CSS (compilé via Vite WebKernel)
      - Fonts : Cormorant Garamond + DM Sans + DM Mono
--}}

<style>
/* ── CDC EASBridge — scoped styles ────────────────────────────── */
.cdc-root {
    --cdc-gold:        #c9a84c;
    --cdc-gold-light:  #e2c97e;
    --cdc-dark:        #0a0a0f;
    --cdc-surface:     #111118;
    --cdc-surface-2:   #1a1a26;
    --cdc-border:      rgba(201,168,76,.18);
    --cdc-text:        #e8e4d9;
    --cdc-muted:       #7a7a8c;
    --cdc-danger:      #e05555;
    --cdc-success:     #4caf7c;
    --cdc-warning:     #e09a2b;
    --cdc-info:        #4a8fe0;
    font-family: 'DM Sans', sans-serif;
    color: var(--cdc-text);
    background: var(--cdc-dark);
    line-height: 1.65;
}

/* Typography */
.cdc-display { font-family: 'Cormorant Garamond', Georgia, serif; }
.cdc-mono    { font-family: 'DM Mono', 'Courier New', monospace; }

/* Layout */
.cdc-wrap { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }
.cdc-section { padding: 3.5rem 0 2.5rem; border-bottom: 1px solid var(--cdc-border); }
.cdc-section:last-child { border-bottom: none; }

/* Cover */
.cdc-cover {
    background: linear-gradient(135deg, #0a0a0f 0%, #12101e 60%, #0e0d17 100%);
    border-bottom: 1px solid var(--cdc-border);
    padding: 4rem 1.5rem 3rem;
    position: relative;
    overflow: hidden;
}
.cdc-cover::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 60% 40% at 80% 30%, rgba(201,168,76,.07) 0%, transparent 70%);
    pointer-events: none;
}
.cdc-cover-eyebrow {
    font-size: .7rem;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--cdc-gold);
    margin-bottom: .75rem;
}
.cdc-cover-title {
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 300;
    line-height: 1.1;
    color: #fff;
    margin: 0 0 .5rem;
}
.cdc-cover-title em { font-style: italic; color: var(--cdc-gold-light); }
.cdc-cover-sub {
    font-size: 1rem;
    color: var(--cdc-muted);
    margin: .5rem 0 2rem;
}
.cdc-cover-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem 1.5rem;
    font-size: .78rem;
    color: var(--cdc-muted);
}
.cdc-cover-meta span { display: flex; align-items: center; gap: .35rem; }
.cdc-cover-meta strong { color: var(--cdc-text); }

/* TOC */
.cdc-toc { list-style: none; padding: 0; margin: 0; display: grid; gap: .3rem; }
.cdc-toc li { display: flex; align-items: baseline; gap: .5rem; }
.cdc-toc-num {
    font-size: .7rem;
    color: var(--cdc-gold);
    font-family: 'DM Mono', monospace;
    min-width: 1.8rem;
}
.cdc-toc a {
    color: var(--cdc-text);
    text-decoration: none;
    font-size: .9rem;
    transition: color .2s;
}
.cdc-toc a:hover { color: var(--cdc-gold-light); }

/* Section headings */
.cdc-h1 {
    font-size: clamp(1.5rem, 3vw, 2.2rem);
    font-weight: 300;
    color: #fff;
    margin: 0 0 .25rem;
}
.cdc-h1 .cdc-num {
    font-size: .75rem;
    color: var(--cdc-gold);
    font-family: 'DM Mono', monospace;
    margin-right: .5rem;
    vertical-align: middle;
}
.cdc-h2 {
    font-size: 1rem;
    font-weight: 500;
    color: var(--cdc-gold-light);
    margin: 2rem 0 .75rem;
    letter-spacing: .04em;
}
.cdc-h3 {
    font-size: .85rem;
    font-weight: 500;
    color: var(--cdc-muted);
    margin: 1.25rem 0 .5rem;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.cdc-lead {
    font-size: .95rem;
    color: var(--cdc-muted);
    margin: 0 0 1.5rem;
    max-width: 72ch;
}

/* Tables */
.cdc-table-wrap { overflow-x: auto; margin: .75rem 0 1.5rem; border-radius: .5rem; }
.cdc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .84rem;
}
.cdc-table thead tr {
    background: var(--cdc-surface-2);
    border-bottom: 1px solid var(--cdc-border);
}
.cdc-table thead th {
    padding: .65rem 1rem;
    text-align: left;
    font-weight: 500;
    color: var(--cdc-gold-light);
    font-size: .75rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
}
.cdc-table tbody tr {
    border-bottom: 1px solid rgba(201,168,76,.08);
    transition: background .15s;
}
.cdc-table tbody tr:last-child { border-bottom: none; }
.cdc-table tbody tr:hover { background: rgba(201,168,76,.04); }
.cdc-table td { padding: .6rem 1rem; vertical-align: top; }
.cdc-table td:first-child { color: var(--cdc-text); }

/* Wireframe tables */
.cdc-wire { border: 1px solid var(--cdc-border); border-radius: .5rem; overflow: hidden; }
.cdc-wire thead tr { background: rgba(201,168,76,.1); }
.cdc-wire thead th { color: var(--cdc-gold); font-size: .72rem; }
.cdc-wire td { color: var(--cdc-muted); font-size: .82rem; }

/* Code blocks */
.cdc-code {
    background: var(--cdc-surface);
    border: 1px solid var(--cdc-border);
    border-radius: .4rem;
    padding: 1rem 1.2rem;
    font-family: 'DM Mono', monospace;
    font-size: .8rem;
    color: #a8c0a0;
    overflow-x: auto;
    margin: .5rem 0 1.25rem;
    line-height: 1.7;
}

/* Grid utils */
.cdc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.cdc-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
@media (max-width: 700px) {
    .cdc-grid-2, .cdc-grid-3 { grid-template-columns: 1fr; }
}

/* Stat cards */
.cdc-stat {
    background: var(--cdc-surface);
    border: 1px solid var(--cdc-border);
    border-radius: .5rem;
    padding: 1rem 1.25rem;
}
.cdc-stat-val {
    font-size: 1.6rem;
    font-weight: 300;
    color: var(--cdc-gold-light);
    font-family: 'Cormorant Garamond', serif;
    line-height: 1.1;
}
.cdc-stat-label { font-size: .75rem; color: var(--cdc-muted); margin-top: .15rem; }

/* Pill badges */
.cdc-pill {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .65rem;
    border-radius: 9999px;
    font-size: .72rem;
    font-weight: 500;
    letter-spacing: .03em;
}
.cdc-pill-gold   { background: rgba(201,168,76,.15); color: var(--cdc-gold-light); border: 1px solid rgba(201,168,76,.25); }
.cdc-pill-danger { background: rgba(224,85,85,.12);  color: #e08888; border: 1px solid rgba(224,85,85,.2); }
.cdc-pill-ok     { background: rgba(76,175,124,.12); color: #7fd4a8; border: 1px solid rgba(76,175,124,.2); }
.cdc-pill-warn   { background: rgba(224,154,43,.12); color: #e0b96a; border: 1px solid rgba(224,154,43,.2); }
.cdc-pill-info   { background: rgba(74,143,224,.12); color: #8ab8e8; border: 1px solid rgba(74,143,224,.2); }

/* Wireframe page preview block */
.cdc-page-preview {
    background: var(--cdc-surface);
    border: 1px solid var(--cdc-border);
    border-radius: .6rem;
    overflow: hidden;
    margin: .5rem 0 1.5rem;
}
.cdc-page-preview-label {
    background: var(--cdc-surface-2);
    border-bottom: 1px solid var(--cdc-border);
    padding: .5rem 1rem;
    font-size: .72rem;
    color: var(--cdc-gold);
    letter-spacing: .08em;
    text-transform: uppercase;
    font-family: 'DM Mono', monospace;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.cdc-page-preview-body { padding: 1rem; }

/* Mermaid placeholder */
.cdc-diagram-placeholder {
    background: var(--cdc-surface);
    border: 1px dashed var(--cdc-border);
    border-radius: .5rem;
    padding: 1.5rem;
    font-family: 'DM Mono', monospace;
    font-size: .78rem;
    color: var(--cdc-muted);
    white-space: pre;
    overflow-x: auto;
    margin: .5rem 0 1.25rem;
    line-height: 1.6;
}

/* Accordion for open questions */
.cdc-accordion { border: 1px solid var(--cdc-border); border-radius: .5rem; overflow: hidden; }
.cdc-accordion-item { border-bottom: 1px solid var(--cdc-border); }
.cdc-accordion-item:last-child { border-bottom: none; }
.cdc-accordion-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .85rem 1.1rem;
    background: none;
    border: none;
    color: var(--cdc-text);
    font-size: .88rem;
    cursor: pointer;
    text-align: left;
    gap: .75rem;
    transition: background .15s;
}
.cdc-accordion-trigger:hover { background: rgba(201,168,76,.04); }
.cdc-accordion-body {
    padding: 0 1.1rem 1rem;
    font-size: .84rem;
    color: var(--cdc-muted);
}

/* Divider */
.cdc-divider { border: none; border-top: 1px solid var(--cdc-border); margin: 1.5rem 0; }

/* Responsive nav */
.cdc-toc-panel {
    position: sticky;
    top: 1rem;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    padding: 1rem 1.25rem;
    background: var(--cdc-surface);
    border: 1px solid var(--cdc-border);
    border-radius: .6rem;
}
.cdc-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 2.5rem;
    align-items: start;
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
}
@media (max-width: 900px) {
    .cdc-layout { grid-template-columns: 1fr; }
    .cdc-toc-panel { position: static; max-height: none; }
}
</style>

<div class="cdc-root" x-data="{ openQ: null }">

{{-- ═══════════════════════════════════════════════════════════════
     COVER
═══════════════════════════════════════════════════════════════ --}}
<div class="cdc-cover">
    <div class="cdc-wrap">
        <p class="cdc-cover-eyebrow">Cahier des Charges Technique · v1.1</p>
        <h1 class="cdc-display cdc-cover-title">
            <em>EASBridge</em>
        </h1>
        <p class="cdc-cover-sub">
            Module WebKernel unique <code class="cdc-mono" style="font-size:.8em;color:var(--cdc-gold)">easbridge-site</code>
            — Site vitrine + Espace partenaire privé
        </p>
        <div class="cdc-cover-meta">
            <span>📅 <strong>02 avril 2026</strong></span>
            <span>⚙️ Mode <strong>B — WebKernel Site-Builder</strong></span>
            <span>🤝 Accord <strong>n° 19CQRCFDP</strong></span>
            <span>🔒 <strong>Document confidentiel — usage interne</strong></span>
        </div>

        <hr class="cdc-divider" style="margin-top:2rem">

        {{-- Filament callout: confidentialité --}}
        <x-filament::callout
            icon="heroicon-o-lock-closed"
            color="warning"
        >
            <x-slot name="heading">Accord de confidentialité actif — 5 ans</x-slot>
            <x-slot name="description">
                Ce document est couvert par l'Article 4.2 de l'accord n° 19CQRCFDP.
                Toute divulgation à un tiers sans accord écrit préalable des deux parties est interdite.
            </x-slot>
        </x-filament::callout>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     LAYOUT : TOC + CONTENU
═══════════════════════════════════════════════════════════════ --}}
<div class="cdc-layout" style="margin-top:2.5rem; padding-bottom:4rem;">

    {{-- ── SIDEBAR TOC ──────────────────────────────────────────── --}}
    <aside>
        <div class="cdc-toc-panel">
            <p class="cdc-h3" style="margin-top:0">Sommaire</p>
            <ul class="cdc-toc">
                <li><span class="cdc-toc-num">01</span><a href="#s1">Vue d'ensemble</a></li>
                <li><span class="cdc-toc-num">02</span><a href="#s2">Architecture technique</a></li>
                <li><span class="cdc-toc-num">03</span><a href="#s3">Wireframes</a></li>
                <li><span class="cdc-toc-num">04</span><a href="#s4">Specs contenu</a></li>
                <li><span class="cdc-toc-num">05</span><a href="#s5">Specs techniques</a></li>
                <li><span class="cdc-toc-num">06</span><a href="#s6">Livrables & planning</a></li>
                <li><span class="cdc-toc-num">07</span><a href="#s7">Questions ouvertes</a></li>
            </ul>

            <hr class="cdc-divider">

            <p class="cdc-h3">Parties</p>
            <div style="display:flex;flex-direction:column;gap:.6rem;font-size:.8rem;">
                <div>
                    <x-filament::badge color="warning" icon="heroicon-m-building-office">
                        Numerimondes
                    </x-filament::badge>
                    <p style="color:var(--cdc-muted);margin:.25rem 0 0;font-size:.75rem;">
                        Casablanca · RC 152844
                    </p>
                </div>
                <div>
                    <x-filament::badge color="info" icon="heroicon-m-user">
                        EASBridge
                    </x-filament::badge>
                    <p style="color:var(--cdc-muted);margin:.25rem 0 0;font-size:.75rem;">
                        Barcelona · NIE Z3275315M
                    </p>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── MAIN CONTENT ──────────────────────────────────────────── --}}
    <main>

{{-- ════════════════════════════════════════════════════════════════
     §1 — VUE D'ENSEMBLE
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s1">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">01</span>Vue d'ensemble
    </h2>

    {{-- 1.1 Contexte --}}
    <h3 class="cdc-h2">1.1 Contexte</h3>
    <p class="cdc-lead">
        EASBridge (www.easbridge.com) est une structure commerciale espagnole (autonomo, Barcelone —
        Taha Laamrani, NIE Z3275315M) opérant sur cinq piliers : stérilisation hospitalière,
        nettoyage premium, compléments alimentaires &amp; pharmacie, distribution de modules
        Webkernel, et travel &amp; conciergerie. Elle est liée à Numerimondes par l'accord
        stratégique n° 19CQRCFDP (mars 2026).
    </p>
    <p class="cdc-lead">
        Le site est livré en un <strong>unique module WebKernel</strong> nommé
        <code class="cdc-mono" style="font-size:.85em;color:var(--cdc-gold)">easbridge-site</code>
        — pages publiques et espace partenaire privé inclus — déployé en self-hosting sur VPS Linux.
        Cette unicité garantit la cohérence architecturale, la maintenabilité à long terme
        et la pleine propriété technologique d'EASBridge.
    </p>

    {{-- 1.2 Objectifs --}}
    <h3 class="cdc-h2">1.2 Objectifs</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Objectif</th>
                    <th>KPI associé</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code class="cdc-mono">O1</code></td>
                    <td>Crédibiliser EASBridge auprès des acheteurs publics ES et européens</td>
                    <td>Taux de rebond &lt; 55 %</td>
                </tr>
                <tr>
                    <td><code class="cdc-mono">O2</code></td>
                    <td>Générer des leads qualifiés via formulaires de devis sectoriels</td>
                    <td>≥ 5 devis / mois</td>
                </tr>
                <tr>
                    <td><code class="cdc-mono">O3</code></td>
                    <td>Référencer EASBridge comme revendeur officiel Webkernel EN / ES</td>
                    <td>1 contrat Webkernel avant juin 2026</td>
                </tr>
                <tr>
                    <td><code class="cdc-mono">O4</code></td>
                    <td>Centraliser la coordination Numerimondes–EASBridge (AO, commissions, docs)</td>
                    <td>≥ 8 docs AO traités / trimestre</td>
                </tr>
                <tr>
                    <td><code class="cdc-mono">O5</code></td>
                    <td>Construire l'autorité SEO organique EN + ES sur les marchés cibles</td>
                    <td>Score Lighthouse ≥ 85</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- 1.3 Audiences --}}
    <h3 class="cdc-h2">1.3 Audiences cibles</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead>
                <tr>
                    <th>Audience</th>
                    <th>Description</th>
                    <th>Langue</th>
                    <th>Accès</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>🏥 Acheteurs publics ES</td>
                    <td>Responsables achats hôpitaux, cliniques, administrations LCSP</td>
                    <td><span class="cdc-pill cdc-pill-info">Espagnol</span></td>
                    <td>Public</td>
                </tr>
                <tr>
                    <td>🏨 Directeurs hôtellerie</td>
                    <td>Hôtels premium, Club Med, résidences Costa del Sol</td>
                    <td><span class="cdc-pill cdc-pill-info">ES / EN</span></td>
                    <td>Public</td>
                </tr>
                <tr>
                    <td>💻 DSI / Directeurs PME</td>
                    <td>Prospects modules Webkernel (CRM, ERP, Medtech, RH)</td>
                    <td><span class="cdc-pill cdc-pill-info">EN / ES</span></td>
                    <td>Public</td>
                </tr>
                <tr>
                    <td>💊 Importateurs MA/Sahel</td>
                    <td>Distributeurs compléments alimentaires</td>
                    <td><span class="cdc-pill cdc-pill-info">Anglais</span></td>
                    <td>Public</td>
                </tr>
                <tr>
                    <td>🔐 Partenaire Numerimondes</td>
                    <td>Coordination interne (Yassine El Moumen)</td>
                    <td><span class="cdc-pill cdc-pill-gold">FR — Admin</span></td>
                    <td>Privé</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- 1.4 Métriques de succès --}}
    <h3 class="cdc-h2">1.4 Métriques de succès (6 mois)</h3>
    <div class="cdc-grid-3">
        <div class="cdc-stat">
            <div class="cdc-stat-val">≥ 5</div>
            <div class="cdc-stat-label">Devis soumis / mois</div>
        </div>
        <div class="cdc-stat">
            <div class="cdc-stat-val">&lt; 2,5s</div>
            <div class="cdc-stat-label">LCP (Largest Contentful Paint)</div>
        </div>
        <div class="cdc-stat">
            <div class="cdc-stat-val">≥ 85</div>
            <div class="cdc-stat-label">Score SEO Lighthouse</div>
        </div>
        <div class="cdc-stat">
            <div class="cdc-stat-val">&lt; 55%</div>
            <div class="cdc-stat-label">Taux de rebond</div>
        </div>
        <div class="cdc-stat">
            <div class="cdc-stat-val">≥ 8</div>
            <div class="cdc-stat-label">Docs AO / trimestre (espace partenaire)</div>
        </div>
        <div class="cdc-stat">
            <div class="cdc-stat-val">2</div>
            <div class="cdc-stat-label">Locales actives (EN + ES)</div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     §2 — ARCHITECTURE TECHNIQUE
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s2">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">02</span>Architecture technique
    </h2>

    {{-- 2.1 Stack --}}
    <h3 class="cdc-h2">2.1 Stack technologique</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead>
                <tr><th>Couche</th><th>Technologie</th><th>Version</th><th>Rôle</th></tr>
            </thead>
            <tbody>
                <tr><td>Core framework</td><td>Laravel</td><td>11.x+</td><td>Fondation applicative</td></tr>
                <tr><td>Admin / Page builder</td><td>Filament PHP</td><td>3.x</td><td>Gestion blocks, settings, partenaire</td></tr>
                <tr><td>Block engine</td><td>WebKernel BlockRegistry</td><td>Noyau Numerimondes</td><td>Composition pages par blocks</td></tr>
                <tr>
                    <td><strong>Module applicatif</strong></td>
                    <td><code class="cdc-mono">easbridge-site</code></td>
                    <td>1.0.0</td>
                    <td><span class="cdc-pill cdc-pill-gold">Module unique — site + partenaire</span></td>
                </tr>
                <tr><td>Templating</td><td>Blade + Livewire</td><td>3.x</td><td>Blocks réactifs</td></tr>
                <tr><td>Base de données</td><td>MySQL</td><td>8.0+</td><td>Blocks, pages, partenaire</td></tr>
                <tr><td>Cache</td><td>Redis</td><td>7.x</td><td>Block output + page cache</td></tr>
                <tr><td>Frontend</td><td>Vite + TailwindCSS + Alpine.js</td><td>Latest</td><td>Assets per-block</td></tr>
                <tr><td>Médias</td><td>Spatie Media Library</td><td>11.x</td><td>WebP auto, srcset</td></tr>
                <tr><td>Auth</td><td>Laravel Sanctum + Filament Shield</td><td>—</td><td>Rôles partner / admin</td></tr>
                <tr><td>Multilingue</td><td>Locale-keyed JSON blocks</td><td>—</td><td>EN (défaut) + ES</td></tr>
                <tr><td>Hébergement</td><td>VPS Linux self-hosted</td><td>OVH / Hetzner</td><td>Self-hosting souverain</td></tr>
                <tr><td>SSL</td><td>Let's Encrypt — HTTP/2</td><td>—</td><td>TLS automatisé</td></tr>
            </tbody>
        </table>
    </div>

    {{-- 2.2 Sitemap --}}
    <h3 class="cdc-h2">2.2 Sitemap</h3>
    <pre class="cdc-diagram-placeholder">{{-- mermaid : graph TD --}}
graph TD
  Home["🏠 Home (/)"]

  Home --> About["À propos (/about)"]
  Home --> Services["Services (/services)"]
  Home --> Blog["Blog (/blog)"]
  Home --> Contact["Contact (/contact)"]
  Home --> Login["🔐 Login (/partner/login)"]

  Services --> S1["/services/sterilisation"]
  Services --> S2["/services/nettoyage"]
  Services --> S3["/services/complements-alimentaires"]
  Services --> S4["/services/logiciels-webkernel"]
  Services --> S5["/services/travel-conciergerie"]

  Blog --> BlogPost["/blog/:slug"]

  Login --> Dashboard["Dashboard (/partner/dashboard)"]
  Dashboard --> AOList["Suivi AO (/partner/tenders)"]
  Dashboard --> Docs["Documents (/partner/documents)"]
  Dashboard --> Commissions["Commissions (/partner/commissions)"]</pre>
    <p style="font-size:.75rem;color:var(--cdc-muted);margin-top:-.5rem;">
        ℹ️ Visualisable sur <a href="https://mermaid.live" target="_blank" style="color:var(--cdc-gold)">mermaid.live</a>
    </p>

    {{-- 2.3 Navigation Flow --}}
    <h3 class="cdc-h2">2.3 Navigation flow</h3>
    <pre class="cdc-diagram-placeholder">{{-- mermaid : flowchart LR --}}
flowchart LR
  Visitor -->|Accueil| Home
  Home -->|CTA principal| Contact
  Home -->|Explore| Services
  Services -->|Sélectionne pilier| Detail["Page service"]
  Detail -->|Formulaire devis| Contact
  Home -->|Lit article| Blog
  Blog -->|CTA| Contact
  Home -->|Login| PartnerAuth["Auth /partner"]
  PartnerAuth -->|Accès validé| Dashboard
  Dashboard -->|Consulte| AOList
  Dashboard -->|Télécharge| Docs
  Dashboard -->|Vérifie| Commissions</pre>

    {{-- 2.4 Data Model --}}
    <h3 class="cdc-h2">2.4 Modèle de données</h3>
    <pre class="cdc-diagram-placeholder">{{-- mermaid : erDiagram --}}
erDiagram
  wk_pages ||--o{ wk_page_blocks : contains
  wk_page_blocks }o--|| wk_block_types : uses

  eb_users ||--o{ eb_partner_documents : uploads
  eb_users ||--o{ eb_tenders : manages
  eb_tenders ||--o{ eb_commissions : generates
  eb_tenders ||--o{ eb_partner_documents : attaches

  eb_tenders {
    int id PK
    string reference
    string title
    enum status "veille|montage|depose|remporte|perdu"
    string sector
    decimal amount_gross
    decimal costs_direct
    decimal amount_net
    decimal commission_rate
    date deadline_at
    date submitted_at
    date result_at
  }

  eb_commissions {
    int id PK
    int tender_id FK
    enum direction "easbridge_to_numerimondes|numerimondes_to_easbridge"
    decimal amount
    enum status "pending|invoiced|paid"
    date due_at
    date paid_at
  }</pre>
    <p style="font-size:.75rem;color:var(--cdc-muted);margin-top:-.5rem;">
        Les tables <code class="cdc-mono">wk_*</code> sont natives WebKernel.
        Les tables <code class="cdc-mono">eb_*</code> sont propres au module <code class="cdc-mono">easbridge-site</code>.
    </p>
</section>

{{-- ════════════════════════════════════════════════════════════════
     §3 — WIREFRAMES
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s3">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">03</span>Wireframes
    </h2>
    <p class="cdc-lead">Chaque block est un composant WebKernel immutable à la phase de rendu.</p>

    {{-- ── 3.1 HOME ── --}}
    <h3 class="cdc-h2">3.1 Home ( / )</h3>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">
            🏠 Block : <code>HeroBlock</code>
        </div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr>
                        <th>Logo</th><th>H1 Headline</th><th>Sous-titre</th><th>CTA primaire</th><th>CTA secondaire</th>
                    </tr></thead>
                    <tbody><tr>
                        <td>🔷 EASBridge</td>
                        <td>Your operational environment, fully managed.</td>
                        <td>Sterilisation · Cleaning · Software · Sourcing · Travel — from Barcelona to Europe</td>
                        <td>Request a quote →</td>
                        <td>Discover our services ↓</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">
            Block : <code>TrustBarBlock</code>
        </div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Pilier 1</th><th>Pilier 2</th><th>Pilier 3</th><th>Pilier 4</th></tr></thead>
                    <tbody><tr>
                        <td>🏥 Healthcare</td>
                        <td>🏨 Hospitality</td>
                        <td>💊 Pharma &amp; Nutrition</td>
                        <td>🖥 Webkernel Partner — ES &amp; EU</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">
            Block : <code>ServiceGridBlock</code> — 3 piliers
        </div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Icône</th><th>Pilier</th><th>Description</th><th>CTA</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>🩺</td>
                            <td>Care &amp; Health</td>
                            <td>Hospital sterilisation, food supplements, pharmaceutical sourcing</td>
                            <td>Explore →</td>
                        </tr>
                        <tr>
                            <td>✨</td>
                            <td>Performance</td>
                            <td>Premium cleaning, travel &amp; concierge, international sourcing</td>
                            <td>Explore →</td>
                        </tr>
                        <tr>
                            <td>⚙️</td>
                            <td>Digital Piloting</td>
                            <td>Webkernel CRM/ERP modules, Medtech, SaaS vertical solutions</td>
                            <td>Explore →</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>StatsRowBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Marchés actifs</th><th>Secteurs</th><th>Pays d'opération</th><th>Partenaire tech</th></tr></thead>
                    <tbody><tr>
                        <td>Espagne + Maroc</td><td>5 piliers</td><td>ES · MA · EU</td><td>Webkernel by Numerimondes</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>TextImageBlock</code> — Argument central</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Titre</th><th>Corps</th><th>CTA</th></tr></thead>
                    <tbody><tr>
                        <td>Agility that large groups cannot offer.</td>
                        <td>EASBridge specialises in managing the operational environment of organisations. We understand how sterilisation, facility services, software tools and supply chain support connect — and act with a dedicated team no large firm can match.</td>
                        <td>Learn about EASBridge →</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>CtaBannerBlock</code> — Footer Home</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Texte</th><th>Bouton</th></tr></thead>
                    <tbody><tr>
                        <td>Ready to submit a tender or source a solution?</td>
                        <td>Contact us today →</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 3.2 SERVICES HUB ── --}}
    <h3 class="cdc-h2">3.2 Services Hub ( /services )</h3>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>HeroBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>H1</th><th>Sous-titre</th></tr></thead>
                    <tbody><tr>
                        <td>Our Services</td>
                        <td>Five pillars. One dedicated partner. From Barcelona to Europe.</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>ServiceGridBlock</code> — 5 services</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Service</th><th>Secteur</th><th>Marchés cibles</th><th>CTA</th></tr></thead>
                    <tbody>
                        <tr><td>🏥 Hospital Sterilisation</td><td>Healthcare</td><td>Hôpitaux publics, cliniques privées, dentistes</td><td>Learn more →</td></tr>
                        <tr><td>✨ Premium Cleaning</td><td>Hospitality</td><td>Hôtels, Club Med, résidences luxe</td><td>Learn more →</td></tr>
                        <tr><td>💊 Food &amp; Pharma Supplements</td><td>Health &amp; Nutrition</td><td>Maroc, Sahel, distribution EU</td><td>Learn more →</td></tr>
                        <tr><td>🖥 Webkernel Software</td><td>Digital / SaaS</td><td>PME, institutions, orgs santé</td><td>Learn more →</td></tr>
                        <tr><td>✈️ Travel &amp; Concierge</td><td>Mobility</td><td>Entreprises, personnels médicaux, événements</td><td>Learn more →</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 3.3 STÉRILISATION ── --}}
    <h3 class="cdc-h2">3.3 Stérilisation ( /services/sterilisation ) — Prioritaire</h3>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>HeroBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>H1</th><th>Description</th><th>Badge normes</th><th>CTA</th></tr></thead>
                    <tbody><tr>
                        <td>Hospital Sterilisation Services</td>
                        <td>Dedicated sterilisation management for hospitals, clinics and surgical centres. Fully compliant, fully traceable.</td>
                        <td>EN ISO 17665-1 · EN ISO 11135 · CE certified</td>
                        <td>Request a quote →</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>FeatureGridBlock</code> — Proposition de valeur</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Icône</th><th>Avantage</th><th>Détail</th></tr></thead>
                    <tbody>
                        <tr><td>⚡</td><td>4h emergency response</td><td>Dedicated team, no concurrent projects during your contract</td></tr>
                        <tr><td>📋</td><td>Full traceability</td><td>Cycle-by-cycle validation protocols and incident reporting</td></tr>
                        <tr><td>🎓</td><td>Staff training included</td><td>Onboarding and periodic refresher sessions</td></tr>
                        <tr><td>🔒</td><td>Regulatory compliance</td><td>EN ISO standards, CE-certified equipment, LCSP-compliant documentation</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>QuoteFormBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Champ</th><th>Type</th><th>Requis</th></tr></thead>
                    <tbody>
                        <tr><td>Organisation name</td><td>text</td><td>✅</td></tr>
                        <tr><td>Contact name</td><td>text</td><td>✅</td></tr>
                        <tr><td>Email</td><td>email</td><td>✅</td></tr>
                        <tr><td>Facility type</td><td>select (hospital / clinic / dental / other)</td><td>✅</td></tr>
                        <tr><td>Monthly volume (units)</td><td>number</td><td>—</td></tr>
                        <tr><td>Message</td><td>textarea</td><td>✅</td></tr>
                        <tr><td>Submit</td><td>button "Request sterilisation quote"</td><td>—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 3.4-3.7 AUTRES SERVICES (condensé) ── --}}
    <h3 class="cdc-h2">3.4–3.7 Autres pages service (pattern identique)</h3>

    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead>
                <tr><th>Page</th><th>URL</th><th>H1</th><th>Blocks spécifiques</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nettoyage</td>
                    <td><code class="cdc-mono">/services/nettoyage</code></td>
                    <td>Premium Cleaning Services</td>
                    <td>HeroBlock · FeatureGridBlock (éco, nocturne, superviseur dédié) · QuoteFormBlock</td>
                </tr>
                <tr>
                    <td>Compléments</td>
                    <td><code class="cdc-mono">/services/complements-alimentaires</code></td>
                    <td>Food &amp; Pharmaceutical Supplements</td>
                    <td>HeroBlock · ProcessStepsBlock (ONSSA, logistique, distribution) · QuoteFormBlock</td>
                </tr>
                <tr>
                    <td>Webkernel</td>
                    <td><code class="cdc-mono">/services/logiciels-webkernel</code></td>
                    <td>Webkernel Software Solutions</td>
                    <td>HeroBlock · PricingTableBlock (noyau gratuit, modules perpétuels) · QuoteFormBlock</td>
                </tr>
                <tr>
                    <td>Travel</td>
                    <td><code class="cdc-mono">/services/travel-conciergerie</code></td>
                    <td>Travel &amp; Concierge</td>
                    <td>HeroBlock · FeatureGridBlock (24/7, corporate, urgences) · QuoteFormBlock</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ── 3.8 À PROPOS ── --}}
    <h3 class="cdc-h2">3.8 À propos ( /about )</h3>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>HeroBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>H1</th><th>Sous-titre</th></tr></thead>
                    <tbody><tr>
                        <td>Who is EASBridge?</td>
                        <td>A Barcelona-based operational partner for healthcare, hospitality and digital organisations across Spain and Europe.</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>TeamCardBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Nom</th><th>Rôle</th><th>Localisation</th><th>Secteurs</th></tr></thead>
                    <tbody><tr>
                        <td>Taha Laamrani</td>
                        <td>Founder &amp; Managing Director</td>
                        <td>Barcelona, España</td>
                        <td>Healthcare · Hospitality · Digital · Sourcing</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>ProcessStepsBlock</code> — Phases de développement</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Phase</th><th>Période</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td>Phase 1 — Agilité</td><td>2026</td><td>Autonomo · Focus stérilisation hospitalière · Flexibilité max</td></tr>
                        <tr><td>Phase 2 — Divisions</td><td>2027</td><td>Sous-divisions commerciales distinctes · Crédibilité sectorielle</td></tr>
                        <tr><td>Phase 3 — Empresa</td><td>2028</td><td>Transformation en SARL espagnole (SL) · Références multi-pays</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>PartnerLogosBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Partenaire</th><th>Rôle</th><th>Lien</th></tr></thead>
                    <tbody>
                        <tr><td>🔷 Numerimondes</td><td>Tech engineering, montage AO, développement Webkernel</td><td>numerimondes.com</td></tr>
                        <tr><td>🌐 Webkernel</td><td>Open-source PHP software kernel</td><td>webkernelphp.com</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 3.9 BLOG ── --}}
    <h3 class="cdc-h2">3.9 Blog ( /blog )</h3>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>BlogGridBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Catégorie</th><th>Titre</th><th>Date</th><th>Extrait</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><span class="cdc-pill cdc-pill-info">Healthcare</span></td>
                            <td>Hospital sterilisation procurement: what buyers look for in 2026</td>
                            <td>Avr. 2026</td>
                            <td>EN ISO compliance requirements and how to structure your technical bid.</td>
                        </tr>
                        <tr>
                            <td><span class="cdc-pill cdc-pill-gold">Software</span></td>
                            <td>Webkernel: sovereign software for institutions</td>
                            <td>Mars 2026</td>
                            <td>Why open-source PHP infrastructure changes the vendor dependency equation.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 3.10 CONTACT ── --}}
    <h3 class="cdc-h2">3.10 Contact / Devis ( /contact )</h3>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>HeroBlock</code></div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>H1</th><th>Sous-titre</th></tr></thead>
                    <tbody><tr>
                        <td>Contact EASBridge</td>
                        <td>For quotes, partnerships, or general enquiries — we respond within 24 hours.</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Block : <code>QuoteFormBlock</code> — Contact général</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Champ</th><th>Type</th><th>Options</th><th>Requis</th></tr></thead>
                    <tbody>
                        <tr><td>Full name</td><td>text</td><td>—</td><td>✅</td></tr>
                        <tr><td>Company / Organisation</td><td>text</td><td>—</td><td>✅</td></tr>
                        <tr><td>Country</td><td>select</td><td>ES / MA / FR / Other</td><td>✅</td></tr>
                        <tr><td>Email</td><td>email</td><td>—</td><td>✅</td></tr>
                        <tr><td>Phone</td><td>tel</td><td>—</td><td>—</td></tr>
                        <tr><td>Subject</td><td>select</td><td>Quote / Partnership / Webkernel demo / Other</td><td>✅</td></tr>
                        <tr><td>Service concerned</td><td>multi-select</td><td>5 piliers EASBridge</td><td>—</td></tr>
                        <tr><td>Message</td><td>textarea</td><td>—</td><td>✅</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 3.11 ESPACE PARTENAIRE ── --}}
    <h3 class="cdc-h2">3.11 Espace partenaire — Login &amp; Dashboard ( /partner )</h3>

    <x-filament::callout icon="heroicon-o-lock-closed" color="info">
        <x-slot name="heading">Accès restreint — Deux utilisateurs</x-slot>
        <x-slot name="description">
            Rôle <code>partner_easbridge</code> (Taha Laamrani) · Rôle <code>partner_numerimondes</code> (Yassine El Moumen) · Rôle <code>super_admin</code> (Numerimondes tech).
            Aucun accès public. Auth Laravel Sanctum + Filament Shield.
        </x-slot>
    </x-filament::callout>

    <div class="cdc-page-preview" style="margin-top:1rem">
        <div class="cdc-page-preview-label">Page : Login ( /partner/login )</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Logo</th><th>Titre</th><th>Champs</th><th>Bouton</th></tr></thead>
                    <tbody><tr>
                        <td>🔷 EASBridge</td>
                        <td>Partner Access — Restricted</td>
                        <td>Email + Password</td>
                        <td>Sign in →</td>
                    </tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Page : Dashboard — Navigation interne</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Tab</th><th>URL</th><th>Accès</th></tr></thead>
                    <tbody>
                        <tr><td>📊 Dashboard</td><td>/partner/dashboard</td><td>Les deux partenaires</td></tr>
                        <tr><td>📋 Appels d'offres</td><td>/partner/tenders</td><td>Les deux partenaires</td></tr>
                        <tr><td>📁 Documents</td><td>/partner/documents</td><td>Les deux partenaires</td></tr>
                        <tr><td>💶 Commissions</td><td>/partner/commissions</td><td>Les deux partenaires</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Page : Dashboard — Widgets (vue d'ensemble)</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Widget</th><th>Contenu</th></tr></thead>
                    <tbody>
                        <tr><td>AO en cours</td><td>Nombre d'appels d'offres actifs, statut par étape (veille / montage / déposé / résultat)</td></tr>
                        <tr><td>Commissions dues</td><td>Montant total impayé + indicateur règle 7 jours (accord Art. 3.5)</td></tr>
                        <tr><td>Documents récents</td><td>3 derniers fichiers partagés avec date et déposant</td></tr>
                        <tr><td>Prochaine échéance AO</td><td>Date limite dépôt AO le plus proche</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Page : Suivi AO ( /partner/tenders )</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Réf.</th><th>Titre</th><th>Secteur</th><th>Statut</th><th>Montant net</th><th>Commission</th><th>Échéance</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><code class="cdc-mono">AO-ES-001</code></td>
                            <td>Stérilisation Hôpital Marbella</td>
                            <td>Santé</td>
                            <td><span class="cdc-pill cdc-pill-warn">Montage</span></td>
                            <td>—</td>
                            <td>20 %</td>
                            <td>15 mai 2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cdc-page-preview">
        <div class="cdc-page-preview-label">Page : Commissions ( /partner/commissions )</div>
        <div class="cdc-page-preview-body">
            <div class="cdc-table-wrap">
                <table class="cdc-table cdc-wire">
                    <thead><tr><th>Opération</th><th>Type</th><th>CA brut</th><th>Coûts directs</th><th>Net</th><th>Taux</th><th>Commission</th><th>Statut</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>AO Stérilisation BCN</td>
                            <td>AO remporté</td>
                            <td>48 000 €</td>
                            <td>6 000 €</td>
                            <td>42 000 €</td>
                            <td>20 %</td>
                            <td><strong>8 400 €</strong></td>
                            <td><span class="cdc-pill cdc-pill-warn">En attente</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p style="font-size:.75rem;color:var(--cdc-muted);margin-top:.5rem;">
                Formule : <code class="cdc-mono">net = CA_brut − coûts_directs_justifiés</code> · Paiement sous 7 jours après réception facture (Art. 3.5)
            </p>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     §4 — SPECS CONTENU
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s4">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">04</span>Spécifications de contenu
    </h2>

    <h3 class="cdc-h2">4.1 Ton &amp; voix éditoriale</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Critère</th><th>Directive</th></tr></thead>
            <tbody>
                <tr><td>Registre</td><td>Professionnel, direct, sobre — jamais commercial ou aguicheur</td></tr>
                <tr><td>Persona</td><td>Expert opérationnel de terrain, pas un cabinet conseil</td></tr>
                <tr><td>Formulation</td><td>Phrases courtes, actives, concrètes ("We handle the filing" plutôt que "Our team takes care of")</td></tr>
                <tr><td>Différenciateur</td><td>Toujours mentionner la dédicace exclusive au client et la réactivité (&lt; 4h stérilisation, &lt; 24h autres)</td></tr>
                <tr><td>Langues</td><td>EN (défaut), ES — interface admin Filament en FR</td></tr>
            </tbody>
        </table>
    </div>

    <h3 class="cdc-h2">4.2 Contenu par page</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Page</th><th>Mots (EN+ES)</th><th>Médias nécessaires</th></tr></thead>
            <tbody>
                <tr><td>Home</td><td>~400</td><td>Hero image (hôpital ou logistique), icônes piliers</td></tr>
                <tr><td>Services Hub</td><td>~150 + 5 cards</td><td>Icônes ou illustrations par pilier</td></tr>
                <tr><td>Stérilisation</td><td>~500</td><td>Photo matériel stérilisation, pictogrammes EN ISO</td></tr>
                <tr><td>Nettoyage</td><td>~400</td><td>Photo hôtel premium, produits éco-certifiés</td></tr>
                <tr><td>Compléments</td><td>~450</td><td>Infographie chaîne logistique</td></tr>
                <tr><td>Webkernel</td><td>~500</td><td>Screenshot interface Webkernel, logo Numerimondes</td></tr>
                <tr><td>Travel</td><td>~300</td><td>Photo déplacement professionnel</td></tr>
                <tr><td>À propos</td><td>~500</td><td>Photo Taha Laamrani (optionnel), carte ES/MA</td></tr>
                <tr><td>Blog</td><td>Variable / article</td><td>Image par article</td></tr>
                <tr><td>Contact</td><td>~100</td><td>Google Maps embed (Barcelona)</td></tr>
                <tr><td>Espace partenaire</td><td>Interface applicative</td><td>Aucun média éditorial</td></tr>
            </tbody>
        </table>
    </div>

    <h3 class="cdc-h2">4.3 SEO — mots-clés prioritaires</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Langue</th><th>Mots-clés</th></tr></thead>
            <tbody>
                <tr>
                    <td><span class="cdc-pill cdc-pill-info">ES</span></td>
                    <td>esterilización hospitalaria España · limpieza hoteles Costa del Sol · software Webkernel España · complementos alimenticios exportación Marruecos</td>
                </tr>
                <tr>
                    <td><span class="cdc-pill cdc-pill-ok">EN</span></td>
                    <td>hospital sterilisation contractor Spain · Webkernel reseller Europe · food supplement export Morocco · premium cleaning hospitality Spain</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     §5 — SPECS TECHNIQUES — MODE B WEBKERNEL (module unique)
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s5">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">05</span>Spécifications techniques
    </h2>

    <x-filament::callout icon="heroicon-o-cube" color="primary">
        <x-slot name="heading">Contrainte architecturale fondamentale</x-slot>
        <x-slot name="description">
            L'intégralité du site — pages publiques, blocks, espace partenaire, resources Filament —
            est contenue dans un unique module WebKernel :
            <code>easbridge-site</code> (namespace <code>EASBridge\Site</code>).
            Aucune séparation en sous-modules. Un seul ServiceProvider. Un seul git repository privé.
        </x-slot>
    </x-filament::callout>

    {{-- 5.1 Structure module --}}
    <h3 class="cdc-h2">5.1 Structure du module <code>easbridge-site</code></h3>
    <pre class="cdc-code">modules/easbridge-site/
├── src/
│   ├── EASBridgeSiteServiceProvider.php   # Enregistrement blocks + resources + routes
│   ├── Blocks/                             # Blocks WebKernel (immutable value objects)
│   │   ├── HeroBlock.php
│   │   ├── TrustBarBlock.php
│   │   ├── ServiceGridBlock.php
│   │   ├── ServiceDetailBlock.php
│   │   ├── FeatureGridBlock.php
│   │   ├── StatsRowBlock.php
│   │   ├── TextImageBlock.php
│   │   ├── CtaBannerBlock.php
│   │   ├── QuoteFormBlock.php
│   │   ├── BlogGridBlock.php
│   │   ├── BlogPostBlock.php
│   │   ├── TeamCardBlock.php
│   │   ├── PartnerLogosBlock.php
│   │   ├── ProcessStepsBlock.php
│   │   └── PricingTableBlock.php
│   ├── Filament/
│   │   └── Resources/
│   │       ├── TenderResource.php          # Appels d'offres
│   │       ├── CommissionResource.php      # Commissions
│   │       └── PartnerDocumentResource.php # Documents partagés
│   ├── Models/
│   │   ├── Tender.php
│   │   ├── Commission.php
│   │   └── PartnerDocument.php
│   ├── ValueObjects/
│   │   └── CommissionCalculator.php        # Immuable — calcul net + commission
│   └── Policies/
│       └── PartnerPolicy.php               # Accès restreint rôles partner_*
├── database/
│   └── migrations/
│       ├── create_eb_tenders_table.php
│       ├── create_eb_commissions_table.php
│       └── create_eb_partner_documents_table.php
├── resources/
│   └── views/
│       └── blocks/
│           ├── hero.blade.php
│           ├── trust-bar.blade.php
│           ├── service-grid.blade.php
│           └── ...                         # Un .blade.php par block
└── composer.json</pre>

    {{-- 5.2 Blocks à développer --}}
    <h3 class="cdc-h2">5.2 Blocks WebKernel à développer</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Block identifier</th><th>Label Filament</th><th>Utilisé sur</th></tr></thead>
            <tbody>
                <tr><td><code class="cdc-mono">hero</code></td><td>Hero Section</td><td>Home, toutes pages service, Contact, About</td></tr>
                <tr><td><code class="cdc-mono">trust_bar</code></td><td>Trust Bar / Logos</td><td>Home</td></tr>
                <tr><td><code class="cdc-mono">service_grid</code></td><td>Services Grid</td><td>Home, /services</td></tr>
                <tr><td><code class="cdc-mono">service_detail</code></td><td>Service Detail Card</td><td>Pages service détaillées</td></tr>
                <tr><td><code class="cdc-mono">feature_grid</code></td><td>Feature Grid</td><td>Stérilisation, Nettoyage, Travel</td></tr>
                <tr><td><code class="cdc-mono">stats_row</code></td><td>Key Figures</td><td>Home, About</td></tr>
                <tr><td><code class="cdc-mono">text_image</code></td><td>Text + Image</td><td>Home (argument central), About</td></tr>
                <tr><td><code class="cdc-mono">cta_banner</code></td><td>Call to Action</td><td>Toutes pages (footer block)</td></tr>
                <tr><td><code class="cdc-mono">quote_form</code></td><td>Quote Request Form</td><td>Contact, pages service</td></tr>
                <tr><td><code class="cdc-mono">blog_grid</code></td><td>Blog Listing</td><td>/blog</td></tr>
                <tr><td><code class="cdc-mono">blog_post</code></td><td>Blog Article</td><td>/blog/:slug</td></tr>
                <tr><td><code class="cdc-mono">team_card</code></td><td>Team Member</td><td>About</td></tr>
                <tr><td><code class="cdc-mono">partner_logos</code></td><td>Partner Logos</td><td>About, Home</td></tr>
                <tr><td><code class="cdc-mono">process_steps</code></td><td>Step-by-step Process</td><td>Compléments, Webkernel</td></tr>
                <tr><td><code class="cdc-mono">pricing_table</code></td><td>Licence / Pricing Table</td><td>/services/logiciels-webkernel</td></tr>
            </tbody>
        </table>
    </div>

    {{-- 5.3 Block contract --}}
    <h3 class="cdc-h2">5.3 Contrat Block WebKernel</h3>
    <pre class="cdc-code">/**
 * Interface BlockContract — WebKernel core
 * Tous les blocks EASBridge implémentent ce contrat.
 * Les blocks sont des value objects immuables à la phase de rendu :
 * les settings sont injectés à la construction, jamais mutés.
 */
interface BlockContract
{
    public static function type(): string;   // e.g. 'hero'
    public static function label(): string;  // Label Filament UI
    public static function schema(): array;  // Filament form fields
    public function render(): View;          // Blade view + data
}

// Enregistrement dans EASBridgeSiteServiceProvider
BlockRegistry::register(HeroBlock::class);
BlockRegistry::register(ServiceGridBlock::class);
BlockRegistry::register(QuoteFormBlock::class);
// ... (15 blocks total)</pre>

    {{-- 5.4 CommissionCalculator --}}
    <h3 class="cdc-h2">5.4 Value Object <code>CommissionCalculator</code> (immuable)</h3>
    <pre class="cdc-code">/**
 * CommissionCalculator — Value Object immuable
 * Formule accord n° 19CQRCFDP, Art. 3.2 / 3.3 / 3.4
 */
final class CommissionCalculator
{
    public function __construct(
        private readonly float $amountGross,
        private readonly float $costsDirectJustified,
        private readonly float $commissionRate, // 0.20 | 0.45 | etc.
    ) {}

    public function net(): float
    {
        return $this->amountGross - $this->costsDirectJustified;
    }

    public function commission(): float
    {
        return round($this->net() * $this->commissionRate, 2);
    }

    public function withRate(float $rate): self
    {
        return new self($this->amountGross, $this->costsDirectJustified, $rate);
    }
}</pre>

    {{-- 5.5 Filament Admin Panels --}}
    <h3 class="cdc-h2">5.5 Panels Filament Admin</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Panel</th><th>Usage</th></tr></thead>
            <tbody>
                <tr><td>Page Builder</td><td>Composition et réordonnancement des blocks par page</td></tr>
                <tr><td>Block Settings</td><td>Configuration contenu + médias par block (locale EN/ES)</td></tr>
                <tr><td>Global Settings</td><td>Logo, couleurs marque, coordonnées, liens sociaux</td></tr>
                <tr><td>Navigation Manager</td><td>Menus header (EN/ES) + footer</td></tr>
                <tr><td>Media Library</td><td>Gestion assets — WebP auto-conversion, srcset</td></tr>
                <tr><td>SEO Manager</td><td>Meta title/description + OG par page et par locale</td></tr>
                <tr><td>Blog Manager</td><td>CRUD articles, catégories, slugs, auteurs</td></tr>
                <tr><td>Redirect Manager</td><td>Redirections 301/302</td></tr>
                <tr><td><strong>TenderResource</strong></td><td>CRUD appels d'offres (espace partenaire)</td></tr>
                <tr><td><strong>CommissionResource</strong></td><td>Suivi commissions, calcul net automatique</td></tr>
                <tr><td><strong>PartnerDocumentResource</strong></td><td>Upload / download fichiers partagés</td></tr>
            </tbody>
        </table>
    </div>

    {{-- 5.6 Rôles --}}
    <h3 class="cdc-h2">5.6 Rôles utilisateur</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Rôle</th><th>Titulaire</th><th>Permissions</th></tr></thead>
            <tbody>
                <tr>
                    <td><code class="cdc-mono">partner_easbridge</code></td>
                    <td>Taha Laamrani</td>
                    <td>Lecture AO, commissions, documents · Création document · Validation AO</td>
                </tr>
                <tr>
                    <td><code class="cdc-mono">partner_numerimondes</code></td>
                    <td>Yassine El Moumen</td>
                    <td>Lecture/écriture full sur TenderResource, CommissionResource, DocumentResource</td>
                </tr>
                <tr>
                    <td><code class="cdc-mono">super_admin</code></td>
                    <td>Numerimondes tech</td>
                    <td>Administration complète du module easbridge-site</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- 5.7 Multilingue --}}
    <h3 class="cdc-h2">5.7 Multilingue</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Aspect</th><th>Implémentation</th></tr></thead>
            <tbody>
                <tr><td>Locales actives</td><td><code class="cdc-mono">en</code> (défaut), <code class="cdc-mono">es</code></td></tr>
                <tr><td>URLs</td><td><code class="cdc-mono">/en/...</code> et <code class="cdc-mono">/es/...</code> — redirection auto sur préférence navigateur</td></tr>
                <tr><td>Block settings</td><td>Locale-keyed JSON : <code class="cdc-mono">{ "en": { "title": "..." }, "es": { "title": "..." } }</code></td></tr>
                <tr><td>Navigation</td><td>Menus header/footer locale-aware via NavigationManager</td></tr>
                <tr><td>Interface admin</td><td>Filament en <code class="cdc-mono">fr</code> (coordination interne)</td></tr>
            </tbody>
        </table>
    </div>

    {{-- 5.8 Formulaires et notifications --}}
    <h3 class="cdc-h2">5.8 Formulaires &amp; notifications</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Formulaire</th><th>Destination</th><th>Notification</th></tr></thead>
            <tbody>
                <tr><td>Quote Request — Contact</td><td>contact@easbridge.com</td><td>Mail + log DB (tagging secteur auto)</td></tr>
                <tr><td>Quote Request — page service</td><td>Idem + tag secteur automatique</td><td>Mail + log DB</td></tr>
                <tr><td>Nouveau document partenaire</td><td>Alerte email aux deux partenaires</td><td>Mail interne</td></tr>
                <tr><td>Nouveau AO créé</td><td>Alerte email</td><td>Mail interne</td></tr>
                <tr><td>Commission due &gt; 7 jours</td><td>Alerte email</td><td>Mail interne (règle Art. 3.5)</td></tr>
            </tbody>
        </table>
    </div>

    {{-- 5.9 Déploiement --}}
    <h3 class="cdc-h2">5.9 Déploiement</h3>
    <pre class="cdc-code">php artisan webkernel:publish
php artisan webkernel:cache-blocks
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link
php artisan queue:work --queue=default,media   # via Supervisor</pre>

    <h3 class="cdc-h3" style="margin-top:1.25rem;">Checklist pré-production</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Item</th><th>Statut</th></tr></thead>
            <tbody>
                <tr><td>Redis connecté et opérationnel</td><td><span class="cdc-pill cdc-pill-danger">À vérifier</span></td></tr>
                <tr><td>SSL Let's Encrypt + HTTP/2 activé</td><td><span class="cdc-pill cdc-pill-danger">À configurer</span></td></tr>
                <tr><td>Chemin admin Filament customisé (<code>/eb-admin</code>)</td><td><span class="cdc-pill cdc-pill-danger">À configurer</span></td></tr>
                <tr><td>TTL cache blocks configurés par environnement</td><td><span class="cdc-pill cdc-pill-danger">À définir</span></td></tr>
                <tr><td>Backup DB quotidien automatisé</td><td><span class="cdc-pill cdc-pill-danger">À configurer</span></td></tr>
                <tr><td><code>.env</code> hors dépôt git</td><td><span class="cdc-pill cdc-pill-ok">Bonne pratique standard</span></td></tr>
                <tr><td>Git repository privé easbridge-site</td><td><span class="cdc-pill cdc-pill-danger">À créer</span></td></tr>
            </tbody>
        </table>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     §6 — LIVRABLES & PLANNING
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s6">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">06</span>Livrables &amp; Planning
    </h2>

    <h3 class="cdc-h2">6.1 Livrables du module <code>easbridge-site</code></h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Livrable</th><th>Responsable</th><th>Format</th></tr></thead>
            <tbody>
                <tr><td>Module <code>easbridge-site</code> complet (blocks + resources + migrations)</td><td>Numerimondes</td><td>PHP — WebKernel module</td></tr>
                <tr><td>15 blocks WebKernel documentés + vues Blade</td><td>Numerimondes</td><td>PHP + Blade</td></tr>
                <tr><td>6 pages publiques composées (Home, Services ×5, About, Blog, Contact)</td><td>Numerimondes</td><td>WebKernel déployé</td></tr>
                <tr><td>Espace partenaire (Dashboard, AO, Commissions, Documents)</td><td>Numerimondes</td><td>Filament Resources</td></tr>
                <tr><td>Config multilingue EN/ES + seeds de contenu initial</td><td>Numerimondes</td><td>Migrations + Seeders</td></tr>
                <tr><td>Formation admin Filament (Taha — 2h)</td><td>Numerimondes</td><td>Session vidéo + doc</td></tr>
                <tr><td>Git repository privé + documentation technique</td><td>Numerimondes</td><td>GitHub / GitLab privé</td></tr>
            </tbody>
        </table>
    </div>

    <h3 class="cdc-h2">6.2 Planning indicatif</h3>
    <div class="cdc-table-wrap">
        <table class="cdc-table">
            <thead><tr><th>Phase</th><th>Contenu</th><th>Durée estimée</th></tr></thead>
            <tbody>
                <tr><td>P1 — Setup &amp; architecture</td><td>Install WebKernel, structure module, DB, Redis, SSL</td><td>3 j</td></tr>
                <tr><td>P2 — 15 Blocks core</td><td>Développement PHP + vues Blade par block</td><td>8 j</td></tr>
                <tr><td>P3 — Pages publiques</td><td>Composition via Filament + contenu EN/ES</td><td>4 j</td></tr>
                <tr><td>P4 — Espace partenaire</td><td>TenderResource, CommissionResource, DocumentResource, Auth</td><td>6 j</td></tr>
                <tr><td>P5 — SEO + formulaires</td><td>Meta, OG, sitemap XML, mails, notifications</td><td>2 j</td></tr>
                <tr><td>P6 — Tests &amp; recette</td><td>Tests fonctionnels, Lighthouse, mobile, cross-browser</td><td>3 j</td></tr>
                <tr><td>P7 — Mise en production</td><td>Deploy VPS, DNS, cache warm, formation Taha</td><td>2 j</td></tr>
                <tr>
                    <td><strong>Total estimé</strong></td>
                    <td></td>
                    <td><strong>~28 jours ouvrés</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 class="cdc-h2">6.3 Conditions financières (accord n° 19CQRCFDP — Axe 2)</h3>
    <x-filament::callout icon="heroicon-o-banknotes" color="success">
        <x-slot name="heading">Rappel — Art. 3.3</x-slot>
        <x-slot name="description">
            Le développement du site EASBridge constitue un module Webkernel sur mesure.
            La commission applicable à EASBridge sur toute revente ultérieure de ce module
            (ou module similaire à un tiers) est de 20 % à 45 % du résultat net.
            Paiement sous 7 jours après réception de facture (Art. 3.5).
            Toute somme versée est non remboursable — elle est déduite des opérations futures (Art. 3.6).
        </x-slot>
    </x-filament::callout>
</section>

{{-- ════════════════════════════════════════════════════════════════
     §7 — QUESTIONS OUVERTES
════════════════════════════════════════════════════════════════ --}}
<section class="cdc-section" id="s7">
    <h2 class="cdc-display cdc-h1">
        <span class="cdc-num">07</span>Questions ouvertes
    </h2>
    <p class="cdc-lead">8 points à lever avant ou pendant le démarrage du développement.</p>

    <div class="cdc-accordion">

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-danger" style="margin-right:.5rem;">🔴 Bloquant</span>
                    Q1 — Charte graphique EASBridge : couleurs et typographie définies ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Logo vectoriel disponible (SVG ou AI) ? Couleurs primaire / secondaire définies ?
                Typographie choisie ? Sans ces éléments, aucune vue Blade ne peut être finalisée.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> EASBridge (Taha)
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-danger" style="margin-right:.5rem;">🔴 Bloquant</span>
                    Q2 — DNS www.easbridge.com : registrar actuel ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Accès au panneau DNS requis pour pointer vers le VPS de production.
                Prévoir délai de propagation 24–48h lors du go-live.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> EASBridge (Taha)
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-danger" style="margin-right:.5rem;">🔴 Bloquant</span>
                    Q3 — Hébergement VPS : qui gère le serveur ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Option A : Numerimondes gère un VPS sur son infrastructure existante.
                Option B : EASBridge loue son propre VPS (OVH / Hetzner recommandé — EU souverain).
                Impacte la facturation d'hébergement et le niveau de dépendance opérationnelle.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> Les deux parties
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-warn" style="margin-right:.5rem;">🟡 Important</span>
                    Q4 — Email contact@easbridge.com configuré ? (MX + SMTP)
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Requis pour l'envoi des notifications de formulaire et les alertes partenaire.
                Recommandé : Brevo (ex-Sendinblue) ou Mailgun — SMTP transactionnel EU.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> EASBridge (Taha)
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-warn" style="margin-right:.5rem;">🟡 Important</span>
                    Q5 — Contenu initial blog : qui rédige les premiers articles ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Recommandation : 2 articles minimum à la mise en ligne pour le SEO.
                Taha fournit les textes, ou Numerimondes rédige sur brief — à clarifier.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> EASBridge (Taha) — décision
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-warn" style="margin-right:.5rem;">🟡 Important</span>
                    Q6 — Photos professionnelles disponibles ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Photo Taha Laamrani, photo d'équipements, références visuelles sectorielles.
                Si non disponibles : librairie stock (Unsplash Pro / Adobe Stock) à prévoir.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> EASBridge (Taha)
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-ok" style="margin-right:.5rem;">🟢 À préciser</span>
                    Q7 — Pièces jointes volumineuses dans l'espace partenaire ? Limite de taille ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Les dossiers AO complets peuvent peser 50–200 Mo.
                Spatie Media Library gère le stockage, mais la limite <code>upload_max_filesize</code>
                PHP et le quota disque VPS sont à dimensionner en conséquence.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> Les deux parties
            </div>
        </div>

        <div class="cdc-accordion-item" x-data="{ open: false }">
            <button class="cdc-accordion-trigger" @click="open = !open">
                <span>
                    <span class="cdc-pill cdc-pill-ok" style="margin-right:.5rem;">🟢 Phase 2</span>
                    Q8 — Intégration future d'un outil de signature électronique ?
                </span>
                <svg :class="open ? 'rotate-180' : ''" style="width:1rem;height:1rem;flex-shrink:0;transition:transform .2s" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="cdc-accordion-body" x-show="open" x-cloak>
                Pour les bons de commande partenaire et les avenants à l'accord 19CQRCFDP.
                Candidats : DocuSign, Yousign (EU), Signaturit (ES). À étudier post-lancement.
                <br><strong style="color:var(--cdc-text);">Responsable :</strong> Les deux parties — Phase 2
            </div>
        </div>

    </div>
</section>

{{-- ── Footer CDC ────────────────────────────────────────────────── --}}
<div style="padding:2rem 0 1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;border-top:1px solid var(--cdc-border);margin-top:2rem;">
    <span style="font-size:.72rem;color:var(--cdc-muted);font-family:'DM Mono',monospace;">
        Confidentiel — CDC EASBridge
    </span>
    <span style="font-size:.72rem;color:var(--cdc-muted);font-family:'DM Mono',monospace;">
        v1.1 — 02 avril 2026 — Numerimondes × EASBridge — Accord n° 19CQRCFDP
    </span>
</div>

    </main>
</div>{{-- /.cdc-layout --}}
</div>{{-- /.cdc-root --}}
