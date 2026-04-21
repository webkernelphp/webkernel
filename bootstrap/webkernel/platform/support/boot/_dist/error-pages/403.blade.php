@php
    view('errors::layout', [
        'code' => 403,
        'severity' => 'WARNING',
        'title' => '403 / Access Forbidden',
        'message' => 'You do not have permission to access this resource.',
        'buttons' => [
            ['label' => 'Back', 'url' => url()->previous()],
            ['label' => 'Home', 'url' => url('/')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
