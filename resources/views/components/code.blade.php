@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="rounded-lg overflow-hidden {{ $vis }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @if(!empty($data['filename']))
        <div class="bg-gray-800 dark:bg-gray-800 text-gray-300 dark:text-gray-300 text-xs px-4 py-2 font-mono">{{ $data['filename'] }}</div>
    @endif
    <pre class="bg-gray-900 dark:bg-gray-900 text-gray-100 dark:text-gray-100 p-4 overflow-x-auto text-sm font-mono"><code>{{ $data['code'] ?? '' }}</code></pre>
</div>
