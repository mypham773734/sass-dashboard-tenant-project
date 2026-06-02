@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    {{-- Header + Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">Projects</h3>
            <p class="text-gray-500 mt-1">Manage and track all projects in the system.</p>
        </div>
        <a href="{{ route('project.create') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors whitespace-nowrap font-medium">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            New Project
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5" />
                        <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    <input id="search-input" type="text" placeholder="Project name..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Status</label>
                <select id="status-filter"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                    Filter
                </button>
                <button class="w-full px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($projects as $project)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $project->id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $project->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $project->description ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $badgeClass = match($project->status) {
                                        'active'    => 'bg-emerald-100 text-emerald-700',
                                        'archived'  => 'bg-gray-100 text-gray-600',
                                        'completed' => 'bg-blue-100 text-blue-700',
                                        default     => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $project->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('project.edit', $project->id) }}"
                                       class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('project.destroy', $project->id) }}" method="POST"
                                          onsubmit="return confirm('Delete this project?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Empty state: @forelse handles this automatically --}}
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No projects found</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new project.</p>
                                <a href="{{ route('project.create') }}"
                                   class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                                    New Project
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($projects->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $projects->firstItem() }}–{{ $projects->lastItem() }} of {{ $projects->total() }}
                </div>
                {{ $projects->links() }}
            </div>
        @endif
    </div>

</main>
@endsection
