@extends('admin.layouts.tenant-settings')

@section('settings-content')
<h4 class="text-lg font-semibold text-gray-900 mb-1">Email Notifications</h4>
<p class="text-sm text-gray-500 mb-4">Choose which events send an email notification.</p>

<form method="POST" action="{{ route('tenant.settings.update', [$tenantId, 'email']) }}">
    @csrf
    @foreach ($settings['email'] as $key => $value)
        <x-admin.setting-toggle
            name="email[{{ $key }}]"
            label="{{ ucwords(str_replace('_', ' ', $key)) }}"
            :checked="$value" />
    @endforeach

    <div class="pt-4 mt-4 border-t border-gray-100">
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
            Save changes
        </button>
    </div>
</form>
@endsection
