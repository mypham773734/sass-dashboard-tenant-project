@extends('admin.layouts.tenant-settings')

@section('settings-content')
<h4 class="text-lg font-semibold text-gray-900 mb-1">Localization</h4>
<p class="text-sm text-gray-500 mb-4">Set the default timezone, language and date format for this tenant.</p>

<form method="POST" action="{{ route('tenant.settings.update', [$tenantId, 'localization']) }}">
    @csrf

    <div class="py-3">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Timezone</label>
        <select name="localization[timezone]" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
            @foreach (['UTC', 'Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'Asia/Singapore', 'Europe/London', 'America/New_York'] as $tz)
                <option value="{{ $tz }}" {{ $settings['localization']['timezone'] === $tz ? 'selected' : '' }}>
                    {{ $tz }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="py-3">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Language</label>
        <select name="localization[locale]" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
            @foreach (['en' => 'English', 'vi' => 'Tiếng Việt'] as $value => $label)
                <option value="{{ $value }}" {{ $settings['localization']['locale'] === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="py-3">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date format</label>
        <select name="localization[date_format]" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
            @foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format)
                <option value="{{ $format }}" {{ $settings['localization']['date_format'] === $format ? 'selected' : '' }}>
                    {{ $format }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="pt-4 mt-4 border-t border-gray-100">
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
            Save changes
        </button>
    </div>
</form>
@endsection
