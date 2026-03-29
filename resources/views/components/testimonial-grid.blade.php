@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $cols = $data['columns'] ?? 3;
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="display:grid;grid-template-columns:repeat({{ $cols }},1fr);gap:1.5rem; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @foreach(($data['testimonials'] ?? []) as $t)
        <div class="border dark:border-gray-700 rounded-xl p-5">
            @if(!empty($t['rating']))
                <div class="text-yellow-400 mb-2">@for($i=0;$i<(int)$t['rating'];$i++)★@endfor</div>
            @endif
            <p class="text-gray-700 dark:text-gray-200 mb-4 italic">"{{ $t['quote'] ?? '' }}"</p>
            <div>
                <div class="font-semibold text-sm">{{ $t['name'] ?? '' }}</div>
                @if(!empty($t['role']))<div class="text-xs text-gray-500 dark:text-gray-400">{{ $t['role'] }}</div>@endif
            </div>
        </div>
    @endforeach
</div>
