@props(['kind' => 'empty'])

@php
    $defaults = [
        'empty' => 'Belum ada data.',
        'loading' => 'Memuat data...',
        'error' => 'Terjadi kesalahan.',
    ];
    $text = trim((string) $slot) !== '' ? (string) $slot : ($defaults[$kind] ?? $defaults['empty']);
@endphp

<div {{ $attributes->merge(['class' => 'px-6 py-8 text-center text-gray-500 italic']) }}>
    {{ $text }}
</div>