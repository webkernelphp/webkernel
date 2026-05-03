@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $style = $data['style'] ?? 'bullet';
    $color = $data['icon_color'] ?? '#3b82f6';
    $marker = match($style) {
        'check' => '✓',
        'arrow' => '→',
        'number' => null,
        'none' => null,
        default => '•',
    };
    $items = array_map(fn($i) => is_array($i) ? ($i['text'] ?? '') : $i, $data['items'] ?? []);
@endphp
<{{ $style === 'number' ? 'ol' : 'ul' }}
    @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
    class="space-y-2 {{ $style === 'none' || $marker ? 'list-none' : 'list-decimal pl-5' }} {{ $vis }} {{ $data['class'] ?? '' }}"
    style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
    {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @foreach($items as $i => $item)
        @if(!empty($item))
            <li class="flex gap-2">
                @if($style === 'number')
                    <span class="font-bold shrink-0" style="color: {{ $color }}">{{ $i + 1 }}.</span>
                @elseif($marker)
                    <span class="shrink-0" style="color: {{ $color }}">{{ $marker }}</span>
                @endif
                <span>{{ $item }}</span>
            </li>
        @endif
    @endforeach
</{{ $style === 'number' ? 'ol' : 'ul' }}>
