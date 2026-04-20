@php
    view('errors::layout', [
        'code' => 419,
        'severity' => 'WARNING',
        'title' => '419 / Session Expired',
        'message' => 'Your session has expired for security reasons. Please try again.',
        'buttons' => [
            ['label' => 'Refresh', 'url' => url()->current()],
            ['label' => 'Login', 'url' => route('login')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
