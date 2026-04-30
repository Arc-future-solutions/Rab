@props([
    'padding' => 'p-6',
    'shadow' => 'shadow-md',
    'border' => 'border-line'
])

<div {{ $attributes->merge(['class' => "bg-white rounded-lg border $border $padding $shadow transition-all duration-200 hover:shadow-lg"]) }}>
    {{ $slot }}
</div>
