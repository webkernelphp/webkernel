<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="inline-flex {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @if(!empty($data['url']))<a href="{{ $data['url'] }}">@endif
    @if(!empty($data['icon']))
        <x-dynamic-component :component="$data['icon']" style="width:{{ $data['size'] ?? '2.5rem' }};height:{{ $data['size'] ?? '2.5rem' }};{{ !empty($data['color']) ? 'color:'.$data['color'] : '' }}" />
    @endif
    @if(!empty($data['url']))</a>@endif
</div>
