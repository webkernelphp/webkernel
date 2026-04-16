{{--
    Webkernel Touch — Demo Mode
    ============================
    Include this file BEFORE the main component to inject realistic test data.
    Nothing in here touches the DB or auth system.

    Usage in a layout / view:
        @include('webkernel::components.webkernel-touch-demo-mode')
        @include('webkernel::components.webkernel-touch')

    Or conditionally (e.g. only in local env):
        @if(app()->isLocal())
            @include('webkernel::components.webkernel-touch-demo-mode')
        @endif
        @include('webkernel::components.webkernel-touch')
--}}
@php
    $wktEnabled = true;

    $wktPanels = [
        ['label' => 'admin',   'url' => '/admin'],
        ['label' => 'app',     'url' => '/app'],
        ['label' => 'horizon', 'url' => '/horizon'],
    ];

    $wktUser = [
        'name'  => 'Demo User',
        'email' => 'demo@webkernel.dev',
    ];

    /*
     * Pre-seeded favorites — will be written to localStorage on first load
     * so the Main tab is never empty in demo mode.
     */
    $wktFavorites = [
        ['url' => '/admin/users',    'title' => 'Users'],
        ['url' => '/admin/settings', 'title' => 'Settings'],
        ['url' => '/admin/logs',     'title' => 'Logs'],
    ];
@endphp
