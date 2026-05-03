@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $anim = \Webkernel\Builders\Website\View\BaseView::animationAttributes($data);
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="prose max-w-none {{ $vis }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! $anim !!}>
    {!! $data['content'] ?? '' !!}
</div>
