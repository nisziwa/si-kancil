@props(['variant' => 'success'])

@php
    $variants = [
        'success' => 'text-green-700 bg-green-50 border-green-300',
        'danger' => 'text-red-700 bg-red-50 border-red-300',
        'info' => 'text-blue-700 bg-blue-50 border-blue-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded border '.($variants[$variant] ?? $variants['info'])]) }}>
    {{ $slot }}
</span>