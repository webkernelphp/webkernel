@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
@if(!empty($data['src']))
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="width: {{ $data['width'] ?? '300px' }}; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    <dotlottie-player src="{{ $data['src'] }}"
                      style="width: 100%"
                      @if(!empty($data['autoplay'])) autoplay @endif
                      @if(!empty($data['loop'])) loop @endif
                      background="transparent"
    ></dotlottie-player>
    <script src="https://unpkg.com/@dotlottie/player-component@2/dist/dotlottie-player.mjs" type="module"></script>
</div>
@endif
