<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="text-center {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    <div class="text-4xl font-bold mb-1">
        <span>{{ $data['prefix'] ?? '' }}</span>
        <span x-data="layupCounter({{ (int)($data['number'] ?? 0) }}, {{ ($data['animate'] ?? true) ? 'true' : 'false' }})" x-intersect.once="start()" x-text="count">0</span>
        <span>{{ $data['suffix'] ?? '' }}</span>
    </div>
    @if(!empty($data['title']))
        <div class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $data['title'] }}</div>
    @endif
</div>
