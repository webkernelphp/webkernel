<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->getMetaTitle() }}</title>

    @if($page->meta['description'] ?? false)
        <meta name="description" content="{{ $page->meta['description'] }}">
        <meta property="og:title" content="{{ $page->getMetaTitle() }}">
        <meta property="og:description" content="{{ $page->meta['description'] }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $page->getUrl() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $page->getMetaTitle() }}">
        <meta name="twitter:description" content="{{ $page->meta['description'] }}">
        @if($page->meta['image'] ?? false)
            <meta property="og:image" content="{{ $page->meta['image'] }}">
            <meta name="twitter:image" content="{{ $page->meta['image'] }}">
        @endif
        <link rel="canonical" href="{{ $page->getUrl() }}">
        @foreach($page->getStructuredData() as $schema)
            <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endforeach
    @endif
    @vite(['resources/css/app.css'])
</head>
<body>
    <div @if($page->id) data-page-id="{{ $page->id }}" @endif>
        @foreach($sections as $section)
            @include('layup::components.section', ['section' => $section])
        @endforeach
    </div>

</body>
</html>
