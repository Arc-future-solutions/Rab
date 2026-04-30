@props([
    'color' => 'primary',
    'size' => 'sm'
])

@php
    $bgColors = [
        'primary' => 'bg-primary',
        'secondary' => 'bg-secondary',
        'action' => 'bg-action',
        'highlight' => 'bg-highlight',
    ];
    $textColors = [
        'primary' => 'text-white',
        'secondary' => 'text-white',
        'action' => 'text-white',
        'highlight' => 'text-primary',
    ];
    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
        'lg' => 'px-4 py-2 text-base',
    ];

    $bgClass = $bgColors[$color] ?? $bgColors['primary'];
    $textClass = $textColors[$color] ?? $textColors['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
@endphp

<span {{ $attributes->merge(['class' => "inline-block rounded-full font-bold uppercase tracking-wider $bgClass $textClass $sizeClass"]) }}>
    {{ $slot }}
</span>
