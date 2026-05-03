@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="space-y-6 {{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @foreach(($data['releases'] ?? []) as $release)
        <div class="border-l-4 pl-4 @if(($release['type'] ?? '') === 'major') border-blue-500 dark:border-blue-600 @elseif(($release['type'] ?? '') === 'patch') border-gray-300 dark:border-gray-600 @else border-green-500 dark:border-green-600 @endif">
            <div class="flex items-center gap-3 mb-2">
                <span class="font-mono font-bold text-lg">v{{ $release['version'] ?? '' }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $release['date'] ?? '' }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full @if(($release['type'] ?? '') === 'major') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 @elseif(($release['type'] ?? '') === 'patch') bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 @else bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 @endif">{{ ucfirst($release['type'] ?? 'minor') }}</span>
            </div>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                @foreach(array_filter(explode("\n", $release['changes'] ?? '')) as $change)
                    <li>• {{ trim($change) }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
