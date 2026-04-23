{{-- webkernel::panels.layout.sidebar._items — nav items, groups, labels, active state, margins (private partial) --}}
<style>
.fi-sidebar-group-label { font-weight: 400 !important; }

aside .fi-sidebar-item,
aside .fi-sidebar-group-btn {
    margin-left:  var(--wds-sidebar-item-margin-left) !important;
    margin-right: calc(var(--wds-sidebar-item-margin-right) * 1.4) !important;
}

aside .fi-sidebar-group-items .fi-sidebar-item.fi-active.fi-sidebar-item-has-url {
    margin-left:  0;
    margin-right: 0;
}

.fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn {
    border: 1px solid transparent;
    border-radius: var(--radius-lg);
    padding: 0.4rem 0.6rem;
    transition: border-color 0.15s ease;
}
.fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover,
.fi-sidebar-item.fi-active.fi-sidebar-item-has-url > .fi-sidebar-item-btn {
    border-color: color-mix(in oklab, currentColor 6%, transparent);
}
</style>
