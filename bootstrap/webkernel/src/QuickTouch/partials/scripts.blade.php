{{-- bootstrap/webkernel/backend/quick_touch/quick_touch/partials/scripts.blade.php --}}
<script>
(function () {
    'use strict';

    /* ════════════════════════════════════════════════════════════════════
       Webkernel QuickTouch — behaviour script
       Wrapped in an IIFE so nothing leaks to the global scope except the
       intentional window.wkt* data variables set by the Blade view.
       ════════════════════════════════════════════════════════════════════ */

    /* ── constants ─────────────────────────────────────────────────────── */
    var LS_POS       = 'wkt_pos';
    var LS_FAVS      = 'wkt_favs';
    var PEEK_THRESH  = 30;          /* px from edge to activate peek           */
    var IDLE_MS      = 2800;        /* ms before the button fades to idle      */
    var SIZE         = 52;          /* button diameter in px                   */

    /* ── DOM refs ──────────────────────────────────────────────────────── */
    var root     = document.getElementById('webkernel-touch-root');
    var btn      = document.getElementById('webkernel-touch-btn');
    var panel    = document.getElementById('webkernel-touch-panel');
    var ctx      = document.getElementById('webkernel-touch-ctx');
    var pageInfo = document.getElementById('wkt-page-info');

    /* ── state ─────────────────────────────────────────────────────────── */
    var panelOpen  = false;
    var ctxOpen    = false;
    var isDragging = false;
    var dragOffX   = 0, dragOffY = 0;
    var dragMoved  = false;
    var idleTimer  = null;
    var activeTab  = 'main';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITIES
       ══════════════════════════════════════════════════════════════════════ */

    function clamp(v, lo, hi) { return Math.min(Math.max(v, lo), hi); }

    function savePos(x, y) {
        try { localStorage.setItem(LS_POS, JSON.stringify({ x: x, y: y })); } catch (e) {}
    }
    function loadPos() {
        try { var r = localStorage.getItem(LS_POS); return r ? JSON.parse(r) : null; } catch (e) { return null; }
    }
    function applyPos(x, y) {
        root.style.left = x + 'px';
        root.style.top  = y + 'px';
    }

    /* Peek: hide most of the button when it rests near a screen edge */
    function updatePeek() {
        if (panelOpen || ctxOpen) { root.removeAttribute('data-peek'); return; }
        var x  = parseFloat(root.style.left) || 0;
        var y  = parseFloat(root.style.top)  || 0;
        var bw = window.innerWidth, bh = window.innerHeight;
        if      (x <= PEEK_THRESH)                root.setAttribute('data-peek', 'left');
        else if (x >= bw - SIZE - PEEK_THRESH)    root.setAttribute('data-peek', 'right');
        else if (y <= PEEK_THRESH)                root.setAttribute('data-peek', 'top');
        else if (y >= bh - SIZE - PEEK_THRESH)    root.setAttribute('data-peek', 'bottom');
        else                                      root.removeAttribute('data-peek');
    }

    function initPos() {
        var saved = loadPos();
        var x, y;
        if (saved) {
            x = clamp(saved.x, 0, window.innerWidth  - SIZE);
            y = clamp(saved.y, 0, window.innerHeight - SIZE);
        } else {
            x = window.innerWidth - SIZE - 12;
            y = window.innerHeight - SIZE - 80;
        }
        applyPos(x, y);
        updatePeek();
    }

    /* ══════════════════════════════════════════════════════════════════════
       IDLE TIMER
       ══════════════════════════════════════════════════════════════════════ */

    function setIdle() {
        clearTimeout(idleTimer);
        btn.classList.remove('wkt-idle');
        idleTimer = setTimeout(function () {
            if (!panelOpen && !ctxOpen) {
                btn.classList.add('wkt-idle');
                updatePeek();
            }
        }, IDLE_MS);
    }

    /* ══════════════════════════════════════════════════════════════════════
       PANEL
       ══════════════════════════════════════════════════════════════════════ */

    /* Reposition panel to avoid going off-screen */
    function posPanel() {
        var bw = window.innerWidth, bh = window.innerHeight;
        var rx = parseFloat(root.style.left) || 0;
        var ry = parseFloat(root.style.top)  || 0;
        var pw = 320, ph = panel.offsetHeight || 480;
        var lx = rx - pw - 8;
        if (lx < 8) lx = rx + SIZE + 8;
        if (lx + pw > bw - 8) lx = Math.max(8, bw - pw - 8);
        var ly = ry;
        if (ly + ph > bh - 8) ly = Math.max(8, bh - ph - 8);
        panel.style.left = (lx - rx) + 'px';
        panel.style.top  = (ly - ry) + 'px';
    }

    function openPanel() {
        panelOpen = true;
        panel.classList.add('wkt-panel-open');
        btn.setAttribute('aria-expanded', 'true');
        root.removeAttribute('data-peek');
        btn.classList.remove('wkt-idle');
        closeCtx();
        setTimeout(posPanel, 0);
        renderFavorites();
        renderExtraCtxItems();
        renderExtraQuickActions();
        if (pageInfo) pageInfo.textContent = (document.title || '').substring(0, 22);
    }

    function closePanel() {
        panelOpen = false;
        panel.classList.remove('wkt-panel-open');
        btn.setAttribute('aria-expanded', 'false');
        setIdle();
    }

    /* ══════════════════════════════════════════════════════════════════════
       TABS  (lightweight JS tab switching — works alongside Filament Tabs)
       ══════════════════════════════════════════════════════════════════════ */

    function switchTab(name) {
        activeTab = name;
        /* hide all panes */
        panel.querySelectorAll('.wkt-tab-pane').forEach(function (p) {
            p.style.display = 'none';
        });
        /* show selected pane */
        var pane = document.getElementById('wkt-tab-' + name);
        if (pane) pane.style.display = 'block';

        /* sync Filament tab active states (aria-selected) */
        panel.querySelectorAll('[id^="wkt-tab-btn-"]').forEach(function (b) {
            var isActive = b.id === 'wkt-tab-btn-' + name;
            b.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    /* Wire up Filament tab buttons — they use x-on:click but we also need
       to show/hide our own pane divs */
    document.querySelectorAll('[id^="wkt-tab-btn-"]').forEach(function (tabBtn) {
        tabBtn.addEventListener('click', function () {
            var name = tabBtn.id.replace('wkt-tab-btn-', '');
            switchTab(name);
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       CONTEXT MENU
       ══════════════════════════════════════════════════════════════════════ */

    function posCtx(x, y) {
        var bw = window.innerWidth, bh = window.innerHeight;
        var mw = 230, mh = ctx.offsetHeight || 240;
        var lx = (x + mw > bw - 8) ? x - mw : x;
        var ly = (y + mh > bh - 8) ? y - mh : y;
        ctx.style.left = Math.max(8, lx) + 'px';
        ctx.style.top  = Math.max(8, ly) + 'px';
    }

    function openCtx(x, y) {
        ctxOpen = true;
        ctx.classList.add('wkt-ctx-open');
        closePanel();
        setTimeout(function () { posCtx(x, y); }, 0);
    }

    function closeCtx() {
        ctxOpen = false;
        ctx.classList.remove('wkt-ctx-open');
    }

    /* ══════════════════════════════════════════════════════════════════════
       DRAG — MOUSE
       ══════════════════════════════════════════════════════════════════════ */

    btn.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        isDragging = true;
        dragMoved  = false;
        var r = root.getBoundingClientRect();
        dragOffX = e.clientX - r.left;
        dragOffY = e.clientY - r.top;
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function (e) {
        if (!isDragging) return;
        var nx = clamp(e.clientX - dragOffX, 0, window.innerWidth  - SIZE);
        var ny = clamp(e.clientY - dragOffY, 0, window.innerHeight - SIZE);
        applyPos(nx, ny);
        if (panelOpen) posPanel();
        dragMoved = true;
    });

    document.addEventListener('mouseup', function () {
        if (!isDragging) return;
        isDragging = false;
        document.body.style.userSelect = '';
        if (dragMoved) {
            savePos(parseFloat(root.style.left), parseFloat(root.style.top));
            if (panelOpen) setTimeout(posPanel, 210);
            updatePeek();
        } else {
            panelOpen ? closePanel() : openPanel();
        }
        setIdle();
    });

    /* ══════════════════════════════════════════════════════════════════════
       DRAG — TOUCH
       ══════════════════════════════════════════════════════════════════════ */

    btn.addEventListener('touchstart', function (e) {
        if (e.touches.length !== 1) return;
        var t = e.touches[0];
        isDragging = true;
        dragMoved  = false;
        var r = root.getBoundingClientRect();
        dragOffX = t.clientX - r.left;
        dragOffY = t.clientY - r.top;
        e.preventDefault();
    }, { passive: false });

    btn.addEventListener('touchmove', function (e) {
        if (!isDragging) return;
        var t = e.touches[0];
        var nx = clamp(t.clientX - dragOffX, 0, window.innerWidth  - SIZE);
        var ny = clamp(t.clientY - dragOffY, 0, window.innerHeight - SIZE);
        applyPos(nx, ny);
        if (panelOpen) posPanel();
        dragMoved = true;
        e.preventDefault();
    }, { passive: false });

    btn.addEventListener('touchend', function (e) {
        if (!isDragging) return;
        isDragging = false;
        if (dragMoved) {
            savePos(parseFloat(root.style.left), parseFloat(root.style.top));
            if (panelOpen) setTimeout(posPanel, 210);
            updatePeek();
        } else {
            panelOpen ? closePanel() : openPanel();
        }
        setIdle();
        e.preventDefault();
    }, { passive: false });

    /* ══════════════════════════════════════════════════════════════════════
       RIGHT-CLICK on Filament main area or body
       ══════════════════════════════════════════════════════════════════════ */

    var ctxTarget = document.querySelector('.fi-main') || document.body;
    ctxTarget.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        openCtx(e.clientX, e.clientY);
    });

    /* ══════════════════════════════════════════════════════════════════════
       DISMISS on outside-click / resize
       ══════════════════════════════════════════════════════════════════════ */

    document.addEventListener('click', function (e) {
        if (ctxOpen  && !ctx.contains(e.target))                          closeCtx();
        if (panelOpen && !panel.contains(e.target) && !btn.contains(e.target)) closePanel();
    });

    window.addEventListener('resize', function () {
        if (panelOpen) posPanel();
        if (ctxOpen)   closeCtx();
        initPos();
    });

    /* ══════════════════════════════════════════════════════════════════════
       FAVORITES  (localStorage — synced to DB only when HasQuickTouch present)
       ══════════════════════════════════════════════════════════════════════ */

    function getFavs() {
        try {
            var raw = localStorage.getItem(LS_FAVS);
            if (raw) return JSON.parse(raw);
            /* First load: seed from server-side data */
            if (window.wktFavorites && window.wktFavorites.length) {
                localStorage.setItem(LS_FAVS, JSON.stringify(window.wktFavorites));
                return window.wktFavorites;
            }
            return [];
        } catch (e) { return []; }
    }

    function saveFavs(favs) {
        try { localStorage.setItem(LS_FAVS, JSON.stringify(favs)); } catch (e) {}
    }

    var STAR_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

    function renderFavorites() {
        var list = document.getElementById('wkt-favorites-list');
        if (!list) return;
        var favs = getFavs();
        if (!favs.length) {
            list.innerHTML = '<div class="wkt-item" style="color:var(--wkt-muted);font-size:12px;padding:8px 16px;cursor:default;">No favorites yet — add from the Context tab.</div>';
            return;
        }
        list.innerHTML = '';
        favs.forEach(function (fav, idx) {
            var a = document.createElement('a');
            a.className = 'wkt-item';
            a.href      = fav.url;
            a.innerHTML =
                '<span class="wkt-item-icon">' + STAR_SVG + '</span>' +
                '<span class="wkt-item-label">' + ((fav.title || fav.url) + '').substring(0, 30) + '</span>' +
                '<button class="wkt-item-badge" data-rm="' + idx + '" style="cursor:pointer;background:rgba(239,68,68,0.12);color:#dc2626;" title="Remove favorite" aria-label="Remove">✕</button>';
            list.appendChild(a);
        });

        list.querySelectorAll('[data-rm]').forEach(function (rmBtn) {
            rmBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var f = getFavs();
                f.splice(parseInt(rmBtn.getAttribute('data-rm')), 1);
                saveFavs(f);
                renderFavorites();
            });
        });
    }

    function addFavorite() {
        var favs  = getFavs();
        var url   = window.location.href;
        var title = document.title || url;
        if (!favs.find(function (f) { return f.url === url; })) {
            favs.push({ url: url, title: title });
            saveFavs(favs);
        }
        renderFavorites();
        switchTab('main');
    }

    var addFavBtn    = document.getElementById('wkt-add-fav');
    var ctxAddFavBtn = document.getElementById('wkt-ctx-add-fav');

    if (addFavBtn)    addFavBtn.addEventListener('click',    addFavorite);
    if (ctxAddFavBtn) ctxAddFavBtn.addEventListener('click', function () { addFavorite(); closeCtx(); });

    /* ══════════════════════════════════════════════════════════════════════
       EXTRA CONTEXT ITEMS  (from window.wktContextItems injected by PHP)
       ══════════════════════════════════════════════════════════════════════ */

    function renderExtraCtxItems() {
        var containers = [
            document.getElementById('wkt-extra-ctx-items'),
            document.getElementById('wkt-ctx-extra'),
        ];
        var items = window.wktContextItems || [];
        if (!items.length) return;

        containers.forEach(function (container) {
            if (!container) return;
            container.innerHTML = '';
            items.forEach(function (item) {
                if (item.isDivider) {
                    var d = document.createElement('div');
                    d.className = container.id === 'wkt-ctx-extra' ? 'wkt-ctx-divider' : 'wkt-divider';
                    container.appendChild(d);
                    return;
                }
                var el  = item.url ? document.createElement('a') : document.createElement('button');
                el.className = container.id === 'wkt-ctx-extra' ? 'wkt-ctx-item' : 'wkt-item';
                if (item.url) {
                    el.href = item.url;
                    if (item.newTab) el.target = '_blank';
                }
                if (item.onClick) el.setAttribute('onclick', item.onClick);
                el.innerHTML =
                    '<span class="' + (container.id === 'wkt-ctx-extra' ? 'wkt-ctx-item-icon' : 'wkt-item-icon') + '">' +
                        (item.icon || '') +
                    '</span>' +
                    (item.label || '');
                container.appendChild(el);
            });
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       EXTRA QUICK ACTIONS  (from window.wktGlobalActions injected by PHP)
       ══════════════════════════════════════════════════════════════════════ */

    function renderExtraQuickActions() {
        var container = document.getElementById('wkt-extra-quick-actions');
        if (!container) return;
        container.innerHTML = '';
        var actions = window.wktGlobalActions || [];
        actions.forEach(function (action) {
            var btn = document.createElement('button');
            btn.className = 'wkt-quick-btn';
            btn.title     = action.label || '';
            if (action.onClick) btn.setAttribute('onclick', action.onClick);
            if (action.url) btn.onclick = function () {
                action.newTab ? window.open(action.url, '_blank') : (window.location.href = action.url);
            };
            btn.innerHTML = (action.icon || '') + '<span>' + (action.label || '') + '</span>';
            container.appendChild(btn);
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       FOOTER BUTTONS
       ══════════════════════════════════════════════════════════════════════ */

    var closeBtn    = document.getElementById('wkt-close-btn');
    var openCtxBtn  = document.getElementById('wkt-open-ctx-btn');
    var ctxOpenBtn  = document.getElementById('wkt-ctx-open-panel');

    if (closeBtn)   closeBtn.addEventListener('click',   closePanel);
    if (openCtxBtn) openCtxBtn.addEventListener('click', function () {
        closePanel();
        var r = btn.getBoundingClientRect();
        openCtx(r.right, r.top);
    });
    if (ctxOpenBtn) ctxOpenBtn.addEventListener('click', function () { closeCtx(); openPanel(); });

    /* ══════════════════════════════════════════════════════════════════════
       INIT
       ══════════════════════════════════════════════════════════════════════ */

    function init() {
        initPos();
        setIdle();
        switchTab('main');
        renderFavorites();
        renderExtraCtxItems();
        renderExtraQuickActions();
    }

    /* Re-run on Livewire full-page navigations */
    document.addEventListener('livewire:navigated', init);

    init();

})();
</script>
