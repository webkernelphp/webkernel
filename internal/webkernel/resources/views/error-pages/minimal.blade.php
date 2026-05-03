{{-- errors::minimal --}}

@props([
    'code' => 500,
    'severity' => 'ERROR',
    'title' => 'Error',
    'message' => '',
    'exception' => null,
])

@php
    $page = micro_webpage()
        ->code($code)
        ->severity($severity)
        ->title($title)
        ->message($message)
        ->footer('Powered by WebKernel');

    if (config('app.debug') && $exception) {
        $page->addHtmlComponent(
            micro_webpage_debug()
                ->withException(
                    get_class($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getTraceAsString()
                )
                ->renderToString()
        );
    }

    $page->render();
@endphp
