@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $tag = $data['tag'] ?? 'h2';
    $from = $data['from_color'] ?? '#667eea';
    $to = $data['to_color'] ?? '#764ba2';
    $via = $data['via_color'] ?? '';
    $dir = $data['direction'] ?? 'to right';
    $gradient = $via ? "linear-gradient({$dir}, {$from}, {$via}, {$to})" : "linear-gradient({$dir}, {$from}, {$to})";
@endphp
<{{ $tag }} @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="font-bold {{ $vis }} {{ $data['class'] ?? '' }}"
     style="background: {{ $gradient }}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>{{ $data['text'] ?? '' }}</{{ $tag }}>
