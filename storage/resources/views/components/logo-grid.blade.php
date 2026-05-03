@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $cols = $data['columns'] ?? 4;
    $maxH = $data['max_height'] ?? '3rem';
    $gray = !empty($data['grayscale']);
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @if(!empty($data['title']))
        <p class="text-center text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-6">{{ $data['title'] }}</p>
    @endif
    <div style="display:grid;grid-template-columns:repeat({{ $cols }},1fr);gap:2rem;align-items:center;justify-items:center">
        @foreach(($data['logos'] ?? []) as $logo)
            @php
                $logoSrc = is_array($logo) ? ($logo['src'] ?? $logo['image'] ?? '') : $logo;
                $logoAlt = is_array($logo) ? ($logo['alt'] ?? $logo['name'] ?? '') : '';
            @endphp
            @if(!empty($logoSrc))
                <img src="{{ str_starts_with($logoSrc, 'http') ? $logoSrc : asset('storage/' . $logoSrc) }}" alt="{{ $logoAlt }}" style="max-height:{{ $maxH }};width:auto" class="{{ $gray ? 'grayscale hover:grayscale-0 opacity-60 hover:opacity-100' : '' }} transition-all duration-300" />
            @endif
        @endforeach
    </div>
</div>
