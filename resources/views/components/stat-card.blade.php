@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $color = $data['accent_color'] ?? '#3b82f6';
    $trendColor = match($data['trend'] ?? '') {
        'up' => 'text-green-600',
        'down' => 'text-red-600',
        'neutral' => 'text-gray-500',
        default => 'text-gray-500',
    };
    $trendIcon = match($data['trend'] ?? '') {
        'up' => '↑',
        'down' => '↓',
        'neutral' => '→',
        default => '',
    };
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="border dark:border-gray-700 rounded-xl p-6 {{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }} border-top: 3px solid {{ $color }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ $data['label'] ?? '' }}</div>
    <div class="text-3xl font-bold" style="color: {{ $color }}">{{ $data['value'] ?? '' }}</div>
    @if(!empty($data['description']))
        <div class="text-sm mt-2 {{ $trendColor }}">
            {{ $trendIcon }} {{ $data['description'] }}
        </div>
    @endif
</div>
