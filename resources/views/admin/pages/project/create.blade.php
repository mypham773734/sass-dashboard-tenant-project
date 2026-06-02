@extends('admin.layouts.app')

@push('scripts')
    @vite('resources/js/pages/project.js')
@endpush

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('project.index') }}"
           class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M15 10H5M5 10l4-4M5 10l4 4" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </a>
        <div>
            <h3 class="text-2xl font-bold text-gray-900">
                {{ isset($project) ? 'Edit Project' : 'New Project' }}
            </h3>
            <p class="text-gray-500 mt-1">
                {{ isset($project) ? 'Update the project details below.' : 'Fill in the details to create a new project.' }}
            </p>
        </div>
    </div>

    <form id="project-form" method="POST"
          action="{{ isset($project) ? route('project.update', $project->id) : route('project.store') }}"
          class="space-y-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        @csrf
        @if(isset($project))
            @method('PUT')
        @endif

        @if($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Name --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required
                       placeholder="My Project"
                       value="{{ old('name', $project->name ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>

            {{-- Status --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    <option value="active"    {{ old('status', $project->status ?? 'active') === 'active'    ? 'selected' : '' }}>Active</option>
                    <option value="archived"  {{ old('status', $project->status ?? '')       === 'archived'  ? 'selected' : '' }}>Archived</option>
                    <option value="completed" {{ old('status', $project->status ?? '')       === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            {{-- Description (full width) --}}
            <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="4"
                          placeholder="Brief description of the project..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none">{{ old('description', $project->description ?? '') }}</textarea>
            </div>

        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M15 12v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h3M10 2h6v6M8 10l8-8"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ isset($project) ? 'Update' : 'Save' }}
            </button>
            <a href="{{ route('project.index') }}"
               class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                Cancel
            </a>
        </div>
    </form>

</main>
@endsection
