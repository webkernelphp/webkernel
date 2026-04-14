@php
    view('errors::layout', [
        'code' => 503,
        'severity' => 'INFO',
        'title' => '503 / Service Unavailable',
        'message' => 'The service is temporarily unavailable due to maintenance or high load.',
        'buttons' => [
            ['label' => 'Retry', 'url' => url()->current()],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
