@php
    view('errors::layout', [
        'code' => 429,
        'severity' => 'WARNING',
        'title' => '429 / Too Many Requests',
        'message' => 'Request limit exceeded. Please slow down and try again shortly.',
        'buttons' => [
            ['label' => 'Retry', 'url' => url()->current()],
            ['label' => 'Home', 'url' => url('/')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
