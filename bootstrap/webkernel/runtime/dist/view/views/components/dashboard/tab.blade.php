@props([
    'label' => '',
])

{{--
    This component is used INSIDE a <x-webkernel::dashboard.column tabs> block.
    It renders a semantic marker that Alpine.js will pick up via wcsTabColumn().
    The content is encoded to survive innerHTML injection safely.
--}}
<div
    data-wcs-tab
    data-label="{{ $label }}"
    class="wcs-tab-source-item"
>{{ $slot }}</div>
