@php
    view('errors::layout', [
        'code' => 402,
        'severity' => 'WARNING',
        'title' => '402 / Payment Required',
        'message' => 'A valid payment or subscription is required to continue.',
        'buttons' => [
            ['label' => 'Billing', 'url' => url('/billing')],
            ['label' => 'Home', 'url' => url('/')],
        ],
        'exception' => $exception ?? null,
    ])->render();
@endphp
