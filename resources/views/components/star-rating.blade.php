@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $rating = (float)($data['rating'] ?? 5);
    $max = (int)($data['max'] ?? 5);
    $color = $data['color'] ?? '#facc15';
    $sizeClass = match($data['size'] ?? 'md') {
        'sm' => 'text-lg',
        'lg' => 'text-3xl',
        default => 'text-2xl',
    };
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="inline-flex items-center gap-2 {{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    <span class="{{ $sizeClass }}" style="color: {{ $color }}">
        @for($i = 1; $i <= $max; $i++)
            @if($i <= floor($rating))★@elseif($i - 0.5 <= $rating)★@else☆@endif
        @endfor
    </span>
    @if(!empty($data['label']))
        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $data['label'] }}</span>
    @endif
</div>
