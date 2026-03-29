<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="{{ ($data['layout'] ?? 'top') === 'left' ? 'flex gap-4' : (($data['layout'] ?? 'top') === 'right' ? 'flex flex-row-reverse gap-4' : '') }} {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }} @if(!empty($data['text_alignment']))text-align: {{ $data['text_alignment'] }};@endif" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @if(($data['media_type'] ?? 'none') === 'image' && !empty($data['image']))
        <div class="{{ ($data['layout'] ?? 'top') === 'left' ? 'shrink-0' : 'mb-4' }}">
            <img src="{{ asset('storage/' . $data['image']) }}" alt="{{ $data['title'] ?? '' }}" class="max-w-full h-auto rounded" />
        </div>
    @endif
    <div>
        @if(!empty($data['title']))
            <h3 class="text-xl font-semibold mb-2">
                @if(!empty($data['url']))<a href="{{ $data['url'] }}" class="hover:underline">@endif
                {{ $data['title'] }}
                @if(!empty($data['url']))</a>@endif
            </h3>
        @endif
        @if(!empty($data['content']))
            <div class="prose max-w-none text-gray-600 dark:text-gray-300">
                {!! $data['content'] !!}
            </div>
        @endif
    </div>
</div>
