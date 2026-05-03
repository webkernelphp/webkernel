@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
     x-data="{ open: {{ !empty($data['start_open']) ? 'true' : 'false' }} }"
>
    <div x-show="open" x-collapse class="prose">{!! $data['content'] ?? '' !!}</div>
    <button @click="open = !open" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium mt-2" x-text="open ? '{{ $data['hide_text'] ?? __('layup::frontend.content_toggle.show_less') }}' : '{{ $data['show_text'] ?? __('layup::frontend.content_toggle.show_more') }}'"></button>
</div>
