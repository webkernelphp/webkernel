@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="display:grid;grid-template-columns:repeat({{ $data['columns'] ?? 3 }},1fr);gap:1.5rem; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @foreach(($data['features'] ?? []) as $f)
        <div class="text-center p-4">
            <div class="text-3xl mb-3">{{ $f['emoji'] ?? '🚀' }}</div>
            <div class="font-semibold mb-1">{{ $f['title'] ?? '' }}</div>
            @if(!empty($f['description']))<div class="text-sm text-gray-600 dark:text-gray-300">{{ $f['description'] }}</div>@endif
        </div>
    @endforeach
</div>
