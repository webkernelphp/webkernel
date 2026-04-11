{{--
    webkernel::panels.auth.css
    ──────────────────────────
    Injected at every auth render hook (login, register, password-reset).
    Props:
      $bgLight (string) — light-mode background image URL
      $bgDark  (string) — dark-mode background image URL
--}}
<style>
:root {
    --wds-auth-bg-image-light: url('{{ $bgLight }}');
    --wds-auth-bg-image-dark:  url('{{ $bgDark }}');
    --wds-auth-z-background:   -2;
    --wds-auth-card-max-width: 560px;
    --bg-fi-simple-main:       oklch(96.8% 0.007 247.896);
    --bg-fi-simple-main-dark:  var(--gray-950);
    --shadow-light: rgba(0, 0, 0, 0.08);
    --border-light: rgba(0, 0, 0, 0.06);
    --shadow-dark:  rgba(0, 0, 0, 0.4);
    --border-dark:  rgba(255, 255, 255, 0.08);
    --z-card-shadow: 0;
    --z-card:        1;
    --z-content:     10;
}

/* ── Fullscreen background images ─────────────────────────────────────── */
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: var(--wds-auth-bg-image-light) center / cover no-repeat fixed;
    z-index: var(--wds-auth-z-background);
    opacity: 1;
    transition: opacity 1.5s ease-in-out;
}
body::after {
    content: "";
    position: fixed;
    inset: 0;
    background: var(--wds-auth-bg-image-dark) center / cover no-repeat fixed;
    z-index: var(--wds-auth-z-background);
    opacity: 0;
    transition: opacity 1.5s ease-in-out;
    filter: brightness(0.8);
}
html.dark body::before, .dark body::before { opacity: 0; }
html.dark body::after,  .dark body::after  { opacity: 1; }

@media (max-width: 539px) {
    body::before,
    body::after { display: none !important; }
}

/* ── Base page ────────────────────────────────────────────────────────── */
html, body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* ── Simple (auth) layout ─────────────────────────────────────────────── */
.fi-simple-layout {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    overflow: visible;
}
.fi-simple-main-ctn {
    width: 100%;
    flex-grow: 0;
    height: auto;
    max-width: var(--wds-auth-card-max-width);
    z-index: var(--z-content);
}

/* ── Auth card ────────────────────────────────────────────────────────── */
.fi-simple-main {
    position: relative;
    border-radius: 12px;
    border: 0.5px solid var(--border-light);
    animation: wds-auth-fade-in 1s ease-out;
    padding: 3rem !important;
    z-index: var(--z-card);
}
html:not(.dark) .fi-simple-main {
    background-color: var(--bg-fi-simple-main) !important;
    box-shadow:
        0 2px 4px        color-mix(in srgb, var(--primary-100) 25%, transparent),
        0 8px 16px       color-mix(in srgb, var(--primary-200) 20%, transparent),
        0 32px 64px -12px color-mix(in srgb, var(--primary-400) 15%, transparent),
        inset 0 0 0 0.5px color-mix(in srgb, var(--primary-300) 30%, transparent);
}
html:where(.dark) .fi-simple-main,
:where(.dark) .fi-simple-main {
    background-color: var(--bg-fi-simple-main-dark) !important;
    border: 0.5px solid var(--border-dark);
}

/* Outer glow layer */
.fi-simple-main::before {
    content: "";
    position: absolute;
    border-radius: 12px;
    inset: -4px;
    background: var(--shadow-light);
    border: 0.5px solid var(--border-light);
    pointer-events: none;
    z-index: var(--z-card-shadow);
    transition: all 1.5s ease;
}
:where(.dark) .fi-simple-main::before {
    background: var(--shadow-dark);
    border: 0.5px solid var(--border-dark);
}

/* Reserved for future decoration */
.fi-simple-main::after {
    content: "";
    position: absolute;
    border-radius: 12px;
    inset: -4px;
    background: transparent;
    pointer-events: none;
    z-index: var(--z-card-shadow);
}

.fi-simple-page-content { position: relative; }

@keyframes wds-auth-fade-in {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 640px) {
    .fi-simple-main { --tw-ring-shadow: none; }
}
</style>
