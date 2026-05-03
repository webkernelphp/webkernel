<{{ $data['level'] ?? 'h2' }}
    @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
    class="{{ match($data['level'] ?? 'h2') {
        'h1' => 'text-4xl font-bold',
        'h2' => 'text-3xl font-bold',
        'h3' => 'text-2xl font-semibold',
        'h4' => 'text-xl font-semibold',
        'h5' => 'text-lg font-medium',
        'h6' => 'text-base font-medium',
        default => 'text-3xl font-bold',
    } }} {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }} mb-2"
    style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @if(!empty($data['link_url']))<a href="{{ $data['link_url'] }}" class="hover:underline">@endif{{ $data['content'] ?? '' }}@if(!empty($data['link_url']))</a>@endif
</{{ $data['level'] ?? 'h2' }}>
