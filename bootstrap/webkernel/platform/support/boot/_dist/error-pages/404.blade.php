@php
    view('errors::layout', [
        'code' => 404,
        'severity' => 'INFO',
        'title' => '404 / Page Not Found',
        'message' => 'The requested resource could not be found.',
        'buttons' => [
            ['label' => 'Back', 'url' => url()->previous()],
            ['label' => 'Home', 'url' => url('/')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
