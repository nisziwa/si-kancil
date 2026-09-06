@props(['align' => 'left'])

<th {{ $attributes->merge(['class' => 'px-6 py-3 font-semibold text-gray-600 uppercase text-xs '.($align === 'right' ? 'text-right' : 'text-left')]) }}>
    {{ $slot }}
</th>