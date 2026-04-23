<?php 
    if(isset($tenant)){
        echo json_encode($tenant);
    }
    // die(); 
?>

@extends('admin.layouts.app');

@push('script')
@vite('resources/js/pages/tenant.js')
@endpush


@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">
    <!-- Create New Page -->
    <div id="create-page" class="fade-in">
        <div class="flex items-center gap-4 mb-6">
            <button onclick="showListPage()" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15 10H5M5 10l4-4M5 10l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Add New Tenant</h3>
                <p class="text-gray-500 mt-1">Tạo tenant mới cho hệ thống</p>
            </div>
        </div>

        <form id="create-form" method="POST" action="{{ isset($tenant) ? route('tenant.update', $tenant->slug) : route('tenant.store') }}" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            @csrf
            @if(isset($tenant))
                @method('PUT')
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" autocomplete="FALSE" required placeholder="Cty A" value="{{ isset($tenant) ? $tenant->name : "" }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" required placeholder="cty-a" value="{{ isset($tenant) ? $tenant->slug : '' }}" class="opacity-50 cursor-not-allowed pointer-events-none w-full px-3 py-2 border border-gray-300 rounded-lg text-disable focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Vai trò <span class="text-red-500">*</span></label>
                    <select name="is_active" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="1" {{ isset($tenant) && $tenant->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ isset($tenant) && !$tenant->is_active ? 'selected' : '' }}>No Active</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Trial End at <span class="text-red-500">*</span></label>
                    <input type="date" name="trial_ends_at" required value="{{ isset($tenant) ? date('Y-m-d', strtotime($tenant->trial_ends_at)) : '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M15 12v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h3M10 2h6v6M8 10l8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ isset($tenant) ? 'Cập nhật' : 'Lưu' }}
                </button>
                <a type="button" href="{{ route('tenant.index') }}" class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    Hủy
                </a>
            </div>
        </form>
    </div>

</main>
@endsection 