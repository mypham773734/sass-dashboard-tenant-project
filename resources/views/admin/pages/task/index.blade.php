@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    {{-- Header + Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">Tasks</h3>
            <p class="text-gray-500 mt-1">Manage and track all tasks across projects.</p>
        </div>
        <a href="{{ route('task.create') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors whitespace-nowrap font-medium">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            New Task
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5" />
                        <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    <input type="text" placeholder="Task title..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                </div>
            </div>
            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Status</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="in_review">In Review</option>
                    <option value="done">Done</option>
                </select>
            </div>
            {{-- Priority --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Priority</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                Filter
            </button>
            <button class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                Clear
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Completed At</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tasks as $task)
                        @php
                            $statusClass = match($task->status) {
                                'todo'        => 'bg-slate-100 text-slate-600',
                                'in_progress' => 'bg-indigo-100 text-indigo-700',
                                'in_review'   => 'bg-amber-100 text-amber-700',
                                'done'        => 'bg-emerald-100 text-emerald-700',
                                default       => 'bg-gray-100 text-gray-600',
                            };
                            $statusLabel = match($task->status) {
                                'todo'        => 'To Do',
                                'in_progress' => 'In Progress',
                                'in_review'   => 'In Review',
                                'done'        => 'Done',
                                default       => ucfirst($task->status),
                            };
                            $priorityClass = match($task->priority) {
                                'low'      => 'bg-slate-100 text-slate-500',
                                'medium'   => 'bg-blue-100 text-blue-700',
                                'high'     => 'bg-orange-100 text-orange-700',
                                'critical' => 'bg-red-100 text-red-700',
                                default    => 'bg-gray-100 text-gray-600',
                            };
                            $priorityIcon = match($task->priority) {
                                'low'      => 'fa-arrow-down',
                                'medium'   => 'fa-minus',
                                'high'     => 'fa-arrow-up',
                                'critical' => 'fa-circle-exclamation',
                                default    => 'fa-minus',
                            };
                            $isOverdue = $task->status !== 'done' && $task->dueDate < date('Y-m-d');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900" style="max-width: 180px;">
                                {{ $task->title }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $task->tenantTitle ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $task->projectTitle ?? '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityClass }}">
                                    <i class="fas {{ $priorityIcon }} text-[10px]"></i>
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500" style="max-width: 240px;">
                                <p class="overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    {{ $task->description }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap {{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                @if($isOverdue)
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fas fa-clock text-xs"></i>
                                        {{ \Carbon\Carbon::parse($task->dueDate)->format('d/m/Y') }}
                                    </span>
                                @else
                                    {{ \Carbon\Carbon::parse($task->dueDate)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @if($task->completedAt)
                                    <span class="inline-flex items-center gap-1 text-emerald-600">
                                        <i class="fas fa-check text-xs"></i>
                                        {{ \Carbon\Carbon::parse($task->completedAt)->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('task.edit', $task->id) }}"
                                       class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('task.destroy', $task->id) }}" method="POST"
                                          onsubmit="return confirm('Delete this task?')">
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
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks found</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new task.</p>
                                <a href="{{ route('task.create') }}"
                                   class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                                    New Task
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (active when real paginator is wired up) --}}
        @if($tasks instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $tasks->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} of {{ $tasks->total() }}
                </div>
                {{ $tasks->links() }}
            </div>
        @endif
    </div>

</main>
@endsection
