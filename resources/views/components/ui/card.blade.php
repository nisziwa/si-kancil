@props(['padding' => false])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm'.($padding ? ' p-4' : '')]) }}>
    {{ $slot }}
</div>