@extends('admin.layouts.tenant-settings')

@section('settings-content')
<h4 class="text-lg font-semibold text-gray-900 mb-1">Notifications</h4>
<p class="text-sm text-gray-500 mb-4">Configure how long notifications are kept before being cleaned up.</p>

<form method="POST" action="{{ route('tenant.settings.update', [$tenantId, 'notifications']) }}">
    @csrf

    <div class="py-3">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Retention period</label>
        <select name="notifications[retention_days]" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
            @foreach ([7, 14, 30, 60, 90] as $days)
                <option value="{{ $days }}" {{ (int) $settings['notifications']['retention_days'] === $days ? 'selected' : '' }}>
                    {{ $days }} days
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
