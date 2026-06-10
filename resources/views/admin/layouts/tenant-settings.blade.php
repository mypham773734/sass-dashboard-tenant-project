@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-900">Settings</h3>
        <p class="text-gray-500 mt-1">{{ $tenant->name }}</p>
    </div>

    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-6">
        <x-admin.settings-sidebar :tenant-id="$tenantId" :active="$section" />

        <div class="flex-1 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            @yield('settings-content')
        </div>
    </div>
</main>
@endsection
