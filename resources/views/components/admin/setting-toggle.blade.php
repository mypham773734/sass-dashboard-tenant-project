@props([
    'name',
    'label',
    'checked' => false,
])

@php
    $id = 'toggle-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
@endphp

<div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
    <label for="{{ $id }}" class="text-sm font-medium text-gray-700">
        {{ $label }}
    </label>

    <label for="{{ $id }}" class="relative inline-flex items-center cursor-pointer">
        <input type="hidden" name="{{ $name }}" value="0">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="{{ $name }}"
            value="1"
            @checked($checked)
            {{ $attributes->merge(['class' => 'sr-only peer']) }}
        >
        <div class="relative w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-300 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5 peer-checked:after:border-white"></div>
    </label>
</div>
