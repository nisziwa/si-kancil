@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'bg-green-100 border-green-400 text-green-700',
        'danger' => 'bg-red-100 border-red-400 text-red-700',
        'info' => 'bg-blue-100 border-blue-400 text-blue-700',
    ];
    if ($type === 'error') {
        $type = 'danger';
    }
@endphp

<div {{ $attributes->merge(['class' => 'border px-4 py-3 rounded relative '.$styles[$type] ?? $styles['info']]) }} role="alert">
    {{ $slot }}
</div>