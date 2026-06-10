@props(['name', 'label', 'checked' => false])

<div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" {{ $checked ? 'checked' : '' }} class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 transition-colors
                    after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                    after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
    </label>
</div>
