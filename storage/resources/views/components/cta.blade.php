<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="rounded-xl p-8 text-center {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }} @if(!empty($data['bg_color']))background-color: {{ $data['bg_color'] }};@else background-color: #f9fafb;@endif @if(!empty($data['text_color_cta']))color: {{ $data['text_color_cta'] }};@endif" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @if(!empty($data['title']))
        <h2 class="text-2xl font-bold mb-3">{{ $data['title'] }}</h2>
    @endif
    @if(!empty($data['content']))
        <div class="prose max-w-2xl mx-auto mb-6 text-gray-600 dark:text-gray-300">
            {!! $data['content'] !!}
        </div>
    @endif
    @if(!empty($data['button_text']))
        @php
            $btnClass = match($data['button_style'] ?? 'primary') {
                'secondary' => 'bg-gray-600 dark:bg-gray-700 text-white hover:bg-gray-700 dark:hover:bg-gray-600',
                'outline' => 'border-2 border-current hover:bg-white/10 dark:hover:bg-black/10',
                default => 'bg-blue-600 dark:bg-blue-700 text-white hover:bg-blue-700 dark:hover:bg-blue-600',
            };
        @endphp
        <a href="{{ $data['button_url'] ?? '#' }}" class="inline-block font-medium px-6 py-3 rounded transition-colors {{ $btnClass }}" @if(!empty($data['new_tab'])) target="_blank" rel="noopener noreferrer" @endif>
            {{ $data['button_text'] }}
        </a>
    @endif
</div>
