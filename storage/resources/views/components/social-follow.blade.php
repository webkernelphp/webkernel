@php
    $iconSize = match($data['icon_size'] ?? 'md') {
        'sm' => 'text-sm gap-2',
        'lg' => 'text-lg gap-4',
        default => 'text-base gap-3',
    };
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="flex flex-wrap {{ $iconSize }} {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @foreach(($data['links'] ?? []) as $link)
        @if(!empty($link['url']))
            <a href="{{ $link['url'] }}" class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors" @if($data['new_tab'] ?? true)target="_blank" rel="noopener noreferrer"@endif>
                <span>{{ ucfirst($link['network'] ?? '') }}</span>
            </a>
        @endif
    @endforeach
</div>
