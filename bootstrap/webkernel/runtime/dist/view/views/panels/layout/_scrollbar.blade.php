{{-- webkernel::panels.layout._scrollbar — thin scrollbar global (webkit + firefox) (private partial)
<style>
/* Firefox */
* {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, var(--wds-scrollbar-opacity)) transparent;
}
.dark * {
    scrollbar-color: rgba(255, 255, 255, var(--wds-scrollbar-opacity)) transparent;
}

/* Webkit */
*::-webkit-scrollbar        { width: var(--wds-scrollbar-size); height: var(--wds-scrollbar-size); }
*::-webkit-scrollbar-track  { background: transparent; }
*::-webkit-scrollbar-thumb  {
    background:    rgba(0, 0, 0, var(--wds-scrollbar-opacity));
    border-radius: var(--wds-radius-container);
}
*::-webkit-scrollbar-thumb:hover       { background: rgba(0, 0, 0, var(--wds-scrollbar-opacity-hover)); }
.dark *::-webkit-scrollbar-thumb       { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity)); }
.dark *::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity-hover)); }
</style>
 --}}
