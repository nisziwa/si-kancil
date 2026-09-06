@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $variants = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white font-bold shadow',
        'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold',
        'muted' => 'bg-gray-500 hover:bg-gray-700 text-white font-bold',
        'edit' => 'text-indigo-700 bg-indigo-50 border border-indigo-300 hover:bg-indigo-100 font-semibold',
        'success' => 'text-green-700 bg-green-50 border border-green-300 hover:bg-green-100 font-semibold',
        'warn' => 'text-orange-700 bg-orange-50 border border-orange-300 hover:bg-orange-100 font-semibold',
        'danger' => 'text-red-700 bg-red-50 border border-red-300 hover:bg-red-100 font-semibold',
    ];
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
    ];
    $class = 'inline-flex items-center rounded '.$sizes[$size].' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif