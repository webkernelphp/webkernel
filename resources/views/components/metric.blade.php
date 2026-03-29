@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $cols = $data['columns'] ?? 4;
    $style = $data['style'] ?? 'plain';
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="display:grid;grid-template-columns:repeat({{ $cols }},1fr);gap:1.5rem; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @foreach(($data['metrics'] ?? []) as $m)
        <div class="text-center @if($style === 'bordered') border-r dark:border-gray-700 last:border-r-0 @elseif($style === 'cards') border dark:border-gray-700 rounded-lg p-4 @else py-2 @endif">
            <div class="text-3xl font-bold">{{ $m['prefix'] ?? '' }}{{ $m['value'] ?? '' }}{{ $m['suffix'] ?? '' }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $m['label'] ?? '' }}</div>
        </div>
    @endforeach
</div>
