@php
    $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []);
    $lightbox = !empty($data['lightbox']);
    $showCaptions = !empty($data['show_captions']);
    $captions = $showCaptions ? array_map('trim', explode("\n", $data['captions_text'] ?? '')) : [];
@endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="display:grid;grid-template-columns:repeat({{ $data['columns'] ?? 3 }},1fr);gap:{{ $data['gap'] ?? '0.5rem' }}; {{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
     @if($lightbox) x-data="layupLightbox()" @endif
>
    @foreach(($data['images'] ?? []) as $idx => $image)
        @php
            $imgSrc = is_array($image) ? ($image['src'] ?? $image['image'] ?? $image['url'] ?? '') : $image;
            $imgAlt = is_array($image) ? ($image['alt'] ?? $image['caption'] ?? '') : ($captions[$idx] ?? '');
            $imgUrl = str_starts_with($imgSrc, 'http') ? $imgSrc : asset('storage/' . $imgSrc);
        @endphp
        @if(!empty($imgSrc))
            <div class="overflow-hidden rounded">
                @if($lightbox)
                    <img src="{{ $imgUrl }}" alt="{{ $imgAlt }}" loading="lazy"
                         class="w-full h-auto block hover:scale-105 transition-transform duration-300 cursor-pointer"
                         data-lightbox-src="{{ $imgUrl }}"
                         @click="show('{{ $imgUrl }}')" />
                @else
                    <img src="{{ $imgUrl }}" alt="{{ $imgAlt }}" loading="lazy"
                         class="w-full h-auto block hover:scale-105 transition-transform duration-300" />
                @endif
                @if($showCaptions && !empty($captions[$idx]))
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 px-1">{{ $captions[$idx] }}</p>
                @endif
            </div>
        @endif
    @endforeach

    @if($lightbox)
        <template x-teleport="body">
            <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/90" @click.self="close()" @keydown.escape.window="close()" @keydown.arrow-right.window="next()" @keydown.arrow-left.window="prev()">
                <button @click="close()" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300">&times;</button>
                <button @click="prev()" class="absolute left-4 text-white text-3xl hover:text-gray-300">&#8249;</button>
                <img :src="current" class="max-w-[90vw] max-h-[90vh] object-contain" />
                <button @click="next()" class="absolute right-4 text-white text-3xl hover:text-gray-300">&#8250;</button>
            </div>
        </template>
    @endif
</div>
