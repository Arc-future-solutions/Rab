@props([
    'title' => '',
    'subtitle' => '',
    'alignment' => 'center'
])

@php
    $alignClass = $alignment === 'center' ? 'text-center' : ($alignment === 'right' ? 'text-right' : 'text-left');
@endphp

<div class="{{ $alignClass }} mb-12">
    @if($subtitle)
        <h4 class="text-slate font-medium uppercase tracking-wider mb-2">{{ $subtitle }}</h4>
    @endif
    <h2 class="font-serif text-ink text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">
        {{ $title }}
    </h2>
</div>
