@php
    view('errors::layout', [
        'code' => 401,
        'severity' => 'WARNING',
        'title' => '401 / Unauthorized Access',
        'message' => 'Authentication is required to access this resource.',
        'buttons' => [
            ['label' => 'Login', 'url' => route('login')],
            ['label' => 'Home', 'url' => url('/')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
