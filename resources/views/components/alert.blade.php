@props([
    'type' => 'info',
    'title' => null
])

@php
    $styles = [
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'danger' => 'bg-red-50 border-red-200 text-red-800',
    ];
    $style = $styles[$type] ?? $styles['info'];
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-lg border $style flex gap-3 items-start"]) }}>
    <div class="flex-shrink-0">
        @if($type === 'info')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
        @elseif($type === 'success')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.207l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"></path></svg>
        @elseif($type === 'warning')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.C11.5 2 14 4.5 14 7.5c0 3-3 6-6 6s-6-3-6-6c0-3 2.5-5.5 5.5-5.5z" clip-rule="evenodd"></path></svg>
        @elseif($type === 'danger')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 24"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"></path></svg>
        @endif
    </div>
    <div>
        @if($title)
            <strong class="font-bold">{{ $title }}</strong>
        @endif
        <div class="text-sm">{{ $slot }}</div>
    </div>
</div>
