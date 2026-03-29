@if(!empty($data['url']))
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="relative w-full {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="aspect-ratio: {{ $data['aspect'] ?? '16/9' }}; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    <iframe src="{{ $data['embed_url'] ?? $data['url'] }}"
            class="absolute inset-0 w-full h-full rounded"
            frameborder="0"
            allowfullscreen
            @if(!empty($data['title']))title="{{ $data['title'] }}"@endif
            loading="lazy"></iframe>
</div>
@endif
