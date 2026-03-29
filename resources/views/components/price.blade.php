@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $sizeClass = match($data['size'] ?? 'lg') {
        'sm' => 'text-2xl',
        'md' => 'text-3xl',
        'xl' => 'text-6xl',
        default => 'text-4xl',
    };
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @if(!empty($data['label']))<div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $data['label'] }}</div>@endif
    <div class="flex items-baseline gap-1">
        @if(!empty($data['original_amount']))
            <span class="text-lg text-gray-400 dark:text-gray-500 line-through">{{ $data['currency_symbol'] ?? '$' }}{{ $data['original_amount'] }}</span>
        @endif
        <span class="{{ $sizeClass }} font-bold">{{ $data['currency_symbol'] ?? '$' }}{{ $data['amount'] ?? '' }}</span>
        @if(!empty($data['period']))<span class="text-gray-500 dark:text-gray-400">{{ $data['period'] }}</span>@endif
    </div>
</div>
