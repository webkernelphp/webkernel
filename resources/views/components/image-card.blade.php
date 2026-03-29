@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="border dark:border-gray-700 rounded-xl overflow-hidden {{ $vis }} {{ $data['class'] ?? '' }}" style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @if(!empty($data['image']))<img src="{{ asset('storage/' . $data['image']) }}" alt="{{ $data['title'] ?? '' }}" class="w-full h-48 object-cover" />@endif
    <div class="p-5">
        <h3 class="font-semibold text-lg mb-2">{{ $data['title'] ?? '' }}</h3>
        @if(!empty($data['description']))<p class="text-gray-600 dark:text-gray-300 text-sm mb-3">{{ $data['description'] }}</p>@endif
        @if(!empty($data['link_url']))<a href="{{ $data['link_url'] }}" class="text-blue-600 dark:text-blue-400 text-sm font-medium hover:underline">{{ $data['link_text'] ?? __('layup::frontend.image_card.read_more') . ' →' }}</a>@endif
    </div>
</div>
