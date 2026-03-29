<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="{{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    {!! $data['content'] ?? '' !!}
</div>
