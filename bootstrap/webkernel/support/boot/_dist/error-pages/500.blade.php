@php
    view('errors::layout', [
        'code' => 500,
        'severity' => 'CRITICAL',
        'title' => '500 / Internal Server Error',
        'message' => 'An unexpected error occurred. Our team has been notified.',
        'buttons' => [
            ['label' => 'Back', 'url' => url()->previous()],
            ['label' => 'Home', 'url' => url('/')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
