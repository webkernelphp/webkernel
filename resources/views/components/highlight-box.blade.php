@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $v = $data['variant'] ?? 'info';
    $styles = match($v) {
        'tip' => 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-600 text-green-800 dark:text-green-200',
        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-600 text-yellow-800 dark:text-yellow-200',
        'important' => 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-600 text-red-800 dark:text-red-200',
        'note' => 'bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200',
        default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-600 text-blue-800 dark:text-blue-200',
    };
    $icons = match($v) {
        'tip' => '💚', 'warning' => '⚠️', 'important' => '❗', 'note' => '📝', default => '💡',
    };
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="border-l-4 rounded-r-lg p-4 {{ $styles }} {{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    <div class="flex gap-2 items-start">
        <span class="text-lg">{{ $data['icon'] ?? $icons }}</span>
        <div>
            @if(!empty($data['title']))<div class="font-semibold mb-1">{{ $data['title'] }}</div>@endif
            @if(!empty($data['content']))<div class="prose prose-sm">{!! $data['content'] !!}</div>@endif
        </div>
    </div>
</div>
