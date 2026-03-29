<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif class="rounded overflow-hidden {{ \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []) }} {{ $data['class'] ?? '' }}" style="height:{{ $data['height'] ?? '300px' }};@if(!empty($data['inline_css'])){{ $data['inline_css'] }}@endif" {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}>
    @if(!empty($data['embed']))
        {!! $data['embed'] !!}
    @elseif(!empty($data['address']))
        <iframe
            src="https://maps.google.com/maps?q={{ urlencode($data['address']) }}&z={{ $data['zoom'] ?? '13' }}&output=embed"
            class="w-full h-full border-0"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
    @endif
</div>
