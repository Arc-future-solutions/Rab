@props([
    'variant' => 'primary',
    'type' => 'a',
    'href' => null,
])

@php
    $classes = 'inline-flex items-center justify-center px-6 py-3 rounded-lg font-medium transition-all duration-200 active:scale-95 ';

    if ($variant === 'primary') {
        $classes .= 'bg-primary text-white hover:bg-action shadow-sm hover:shadow-md';
    } elseif ($variant === 'secondary') {
        $classes .= 'bg-white text-ink border border-line hover:bg-cloud shadow-sm';
    } elseif ($variant === 'ghost') {
        $classes .= 'bg-transparent text-slate hover:bg-cloud';
    }
@endphp

@if($type === 'a' && $href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
