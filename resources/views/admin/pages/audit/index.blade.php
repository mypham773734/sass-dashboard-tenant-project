@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">Audit Log</h3>
            <p class="text-gray-500 mt-1">Track all actions performed in this tenant.</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('audit.index') }}" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Action</label>
                <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="">All Actions</option>
                    <optgroup label="Task">
                        <option value="task.created"  @selected(($filters['action'] ?? '') === 'task.created')>Task Created</option>
                        <option value="task.updated"  @selected(($filters['action'] ?? '') === 'task.updated')>Task Updated</option>
                        <option value="task.deleted"  @selected(($filters['action'] ?? '') === 'task.deleted')>Task Deleted</option>
                    </optgroup>
                    <optgroup label="Project">
                        <option value="project.created" @selected(($filters['action'] ?? '') === 'project.created')>Project Created</option>
                        <option value="project.updated" @selected(($filters['action'] ?? '') === 'project.updated')>Project Updated</option>
                        <option value="project.deleted" @selected(($filters['action'] ?? '') === 'project.deleted')>Project Deleted</option>
                    </optgroup>
                    <optgroup label="Auth">
                        <option value="auth.login"        @selected(($filters['action'] ?? '') === 'auth.login')>Login</option>
                        <option value="auth.logout"       @selected(($filters['action'] ?? '') === 'auth.logout')>Logout</option>
                        <option value="auth.login_failed" @selected(($filters['action'] ?? '') === 'auth.login_failed')>Login Failed</option>
                    </optgroup>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">
                    Filter
                </button>
                <a href="{{ route('audit.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 text-sm font-medium transition-colors">
                    Clear
                </a>
            </div>
        </div>
    </form>

    {{-- Timeline --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-8"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Entity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">IP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" x-data>
                    @forelse($logs as $log)
                        @php
                            [$icon, $iconBg, $badgeClass, $label] = match(true) {
                                str_starts_with($log->action, 'task.')    => ['fa-tasks',   'bg-indigo-100 text-indigo-600', 'bg-indigo-50 text-indigo-700',  ucfirst(str_replace('task.',    '', $log->action))],
                                str_starts_with($log->action, 'project.') => ['fa-folder',  'bg-purple-100 text-purple-600', 'bg-purple-50 text-purple-700',  ucfirst(str_replace('project.', '', $log->action))],
                                str_starts_with($log->action, 'auth.')    => ['fa-lock',    'bg-amber-100  text-amber-600',  'bg-amber-50  text-amber-700',   ucfirst(str_replace('auth.',    '', $log->action))],
                                default                                    => ['fa-circle',  'bg-gray-100   text-gray-600',   'bg-gray-50   text-gray-700',    $log->action],
                            };
                            $entityLabel = match(true) {
                                str_starts_with($log->action, 'task.')    => 'Task',
                                str_starts_with($log->action, 'project.') => 'Project',
                                str_starts_with($log->action, 'auth.')    => 'Auth',
                                default                                    => $log->entity_type ?? '—',
                            };
                            $hasValues = ! empty($log->old_values) || ! empty($log->new_values);
                            $rowId = 'row-' . $log->id;
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            {{-- Icon --}}
                            <td class="pl-6 py-4">
                                <div class="w-8 h-8 rounded-full {{ $iconBg }} flex items-center justify-center">
                                    <i class="fas {{ $icon }} text-xs"></i>
                                </div>
                            </td>
                            {{-- Action --}}
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $entityLabel }} · {{ $label }}
                                </span>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $log->action }}</p>
                            </td>
                            {{-- User --}}
                            <td class="px-4 py-4 text-sm text-gray-700 whitespace-nowrap">
                                @if($log->user_id)
                                    <span class="font-medium">User #{{ $log->user_id }}</span>
                                @else
                                    <span class="text-gray-400 italic">System</span>
                                @endif
                            </td>
                            {{-- Entity --}}
                            <td class="px-4 py-4 text-sm text-gray-500 whitespace-nowrap">
                                @if($log->entity_type && $log->entity_id)
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                                        {{ $log->entity_type }} #{{ $log->entity_id }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            {{-- IP --}}
                            <td class="px-4 py-4 text-xs font-mono text-gray-400 whitespace-nowrap">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            {{-- Time --}}
                            <td class="px-4 py-4 text-sm text-gray-500 whitespace-nowrap" title="{{ $log->created_at }}">
                                {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                            </td>
                            {{-- Expand --}}
                            <td class="px-4 py-4">
                                @if($hasValues)
                                    <button type="button"
                                            @click="$el.closest('tr').nextElementSibling.classList.toggle('hidden')"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                        <i class="fas fa-chevron-down text-[10px]"></i> Details
                                    </button>
                                @endif
                            </td>
                        </tr>
                        {{-- Expandable detail row --}}
                        @if($hasValues)
                        <tr class="hidden bg-slate-50 border-b border-gray-100">
                            <td colspan="7" class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                    @if(! empty($log->old_values))
                                    <div>
                                        <p class="font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Before</p>
                                        <pre class="bg-red-50 text-red-700 rounded-lg p-3 overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                    @endif
                                    @if(! empty($log->new_values))
                                    <div>
                                        <p class="font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">After</p>
                                        <pre class="bg-emerald-50 text-emerald-700 rounded-lg p-3 overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                    @endif
                                    @if(! empty($log->metadata))
                                    <div class="md:col-span-2">
                                        <p class="font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Metadata</p>
                                        <pre class="bg-gray-100 text-gray-600 rounded-lg p-3 overflow-x-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <i class="fas fa-history text-4xl text-gray-300 mb-3 block"></i>
                                <p class="text-sm font-medium text-gray-500">No audit logs found</p>
                                <p class="text-xs text-gray-400 mt-1">Actions will appear here as they happen.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
                </div>
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>

</main>
@endsection
