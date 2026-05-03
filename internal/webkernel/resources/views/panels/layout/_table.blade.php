{{-- webkernel::panels.layout._table — ultra-compact Filament table styles (private partial) --}}
<style>
/* ── Core compactness ── */
.fi-ta-table th,
.fi-ta-table td,
.fi-ta-header-cell {
    padding: 6px 10px !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.fi-ta-selection-cell {
    padding-left: 20px !important;
    padding-right: 10px !important;
}
.fi-ta-row { height: 42px !important; }
.fi-ta-main,
.fi-ta-content-ctn,
.fi-ta-ctn { padding: 0 !important; }

/* ── Row hover ── */
@media (hover: hover) {
    .fi-ta-row.fi-clickable:hover {
        background-color: color-mix(in oklab, var(--primary-500) 7%, transparent);
    }
}

/* ── Row lines ── */
.fi-ta-table tbody tr.fi-ta-row {
    border-top: 1px solid color-mix(in oklab, var(--color-white) 12%, transparent) !important;
}
.dark .fi-ta-table tbody tr.fi-ta-row {
    border-top: 1px solid color-mix(in oklab, var(--color-gray-800) 40%, transparent) !important;
}
.fi-ta-table tbody tr.fi-ta-row:first-child { border-top: none !important; }

/* ── Vertical separators ── */
.fi-ta-table td:not(:last-child),
.fi-ta-table th:not(:last-child) {
    border-right: 1px solid color-mix(in oklab, var(--color-white) 8%, transparent) !important;
}
.dark .fi-ta-table td:not(:last-child),
.dark .fi-ta-table th:not(:last-child) {
    border-right: 1px solid color-mix(in oklab, var(--color-gray-700) 25%, transparent) !important;
}

/* ── Per-cell hover ── */
.fi-ta-cell {
    position: relative;
    overflow: hidden;
    transition:
        transform   0.25s cubic-bezier(0.4, 0, 0.2, 1),
        box-shadow  0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
.fi-ta-cell::before {
    content: "";
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 0.28s ease;
    pointer-events: none;
    z-index: 1;
}
.fi-ta-cell:hover::before { opacity: 1; }
.fi-ta-cell:hover .fi-ta-col { color: inherit !important; }

/* ── Header ── */
.fi-ta-header-cell {
    font-size: 12px;
    font-weight: 400;
    opacity: 0.6;
}

/* ── Cell padding ── */
.fi-ta-text {
    padding-top:    0 !important;
    padding-bottom: 0 !important;
    padding-left:   calc(var(--spacing) * 0.6) !important;
    padding-right:  calc(var(--spacing) * 0.6) !important;
}
.fi-ta-icon { transform: scale(0.87); }
.fi-ta-row td,
.fi-ta-row th {
    padding-top:    0 !important;
    padding-bottom: 0 !important;
}

/* ── Container decoration ── */
.fi-ta-ctn {
    position: relative;
    border-radius: var(--wds-radius-container);
    background: var(--bg-default);
}
.fi-ta-ctn::before {
    content: "";
    position: absolute;
    inset: -6px;
    border-radius: calc(var(--wds-radius-container) + 3.5px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.03);
    mix-blend-mode: plus-lighter;
    pointer-events: none;
}

/* ── Horizontal scroll ── */
.fi-ta-content-ctn.fi-fixed-positioning-context {
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}
.fi-ta-content-ctn.fi-fixed-positioning-context::-webkit-scrollbar        { height: 6px; }
.fi-ta-content-ctn.fi-fixed-positioning-context::-webkit-scrollbar-track  { background: transparent; }
.fi-ta-content-ctn.fi-fixed-positioning-context::-webkit-scrollbar-thumb  { background: rgba(255, 255, 255, 0.2); border-radius: 3px; }

/* ── Pagination ── */
nav.fi-pagination {
    font-size: 0.78rem !important;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
}
nav.fi-pagination .fi-pagination-overview {
    font-size: 0.76rem !important;
    padding: 2px 0 !important;
}
.fi-pagination-records-per-page-select-ctn { gap: 4px !important; }
nav.fi-pagination select.fi-select-input {
    height: 26px !important;
    min-width: 52px !important;
    padding: 2px 6px 2px 8px !important;
    font-size: 0.76rem !important;
}
.fi-pagination-records-per-page-select .fi-input-wrp-label {
    font-size: 0.74rem !important;
    padding-right: 3px !important;
}
nav.fi-pagination .fi-btn {
    padding: 3px 7px !important;
    font-size: 0.78rem !important;
    min-height: 26px !important;
}
</style>
