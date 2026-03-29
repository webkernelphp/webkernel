@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $type = $data['type'] ?? 'info';
    $colors = match($type) {
        'success' => 'bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-600 text-green-800 dark:text-green-200',
        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500 dark:border-yellow-600 text-yellow-800 dark:text-yellow-200',
        'danger'  => 'bg-red-50 dark:bg-red-900/20 border-red-500 dark:border-red-600 text-red-800 dark:text-red-200',
        default   => 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 dark:border-blue-600 text-blue-800 dark:text-blue-200',
    };
    $icon = match($type) {
        'success' => '✓',
        'warning' => '⚠',
        'danger'  => '✕',
        default   => 'ℹ',
    };
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="border-l-4 p-4 rounded-r {{ $colors }} {{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
     @if(!empty($data['dismissible'])) x-data="{ show: true }" x-show="show" x-transition @endif
>
    <div class="flex items-start gap-3">
        <span class="text-lg font-bold shrink-0">{{ $icon }}</span>
        <div class="flex-1">
            @if(!empty($data['title']))
                <div class="font-semibold mb-1">{{ $data['title'] }}</div>
            @endif
            @if(!empty($data['content']))
                <div class="text-sm">{!! $data['content'] !!}</div>
            @endif
        </div>
        @if(!empty($data['dismissible']))
            <button @click="show = false" class="text-current opacity-50 hover:opacity-100 shrink-0">&times;</button>
        @endif
    </div>
</div>
