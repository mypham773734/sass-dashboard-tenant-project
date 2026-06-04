@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('task.index') }}"
           class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M15 10H5M5 10l4-4M5 10l4 4" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </a>
        <div>
            <h3 class="text-2xl font-bold text-gray-900">
                {{ isset($task) ? 'Edit Task' : 'New Task' }}
            </h3>
            <p class="text-gray-500 mt-1">
                {{ isset($task) ? 'Update the task details below.' : 'Fill in the details to create a new task.' }}
            </p>
        </div>
    </div>

    <form method="POST"
          action="{{ isset($task) ? route('task.update', $task->id) : route('task.store') }}"
          class="space-y-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        @csrf
        @if(isset($task))
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

        @if(session('error'))
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Project (dropdown từ tenant hiện tại) --}}
            <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                    Project <span class="text-red-500">*</span>
                </label>
                <select name="project_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    <option value="">— Select a project —</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}"
                            {{ old('project_id', $task->projectId ?? '') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @if($projects->isEmpty())
                    <p class="text-xs text-amber-600 mt-1">
                        No projects found for this tenant.
                        <a href="{{ route('project.create') }}" class="underline font-medium">Create one first.</a>
                    </p>
                @endif
            </div>

            {{-- Title --}}
            <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" required
                       placeholder="Task title..."
                       value="{{ old('title', $task->title ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>

            {{-- Status --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    @php $currentStatus = old('status', $task->status ?? 'todo'); @endphp
                    <option value="todo"        {{ $currentStatus === 'todo'        ? 'selected' : '' }}>To Do</option>
                    <option value="in_progress" {{ $currentStatus === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="in_review"   {{ $currentStatus === 'in_review'   ? 'selected' : '' }}>In Review</option>
                    <option value="done"        {{ $currentStatus === 'done'        ? 'selected' : '' }}>Done</option>
                </select>
            </div>

            {{-- Priority --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    Priority <span class="text-red-500">*</span>
                </label>
                <select name="priority" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    @php $currentPriority = old('priority', $task->priority ?? 'medium'); @endphp
                    <option value="low"      {{ $currentPriority === 'low'      ? 'selected' : '' }}>Low</option>
                    <option value="medium"   {{ $currentPriority === 'medium'   ? 'selected' : '' }}>Medium</option>
                    <option value="high"     {{ $currentPriority === 'high'     ? 'selected' : '' }}>High</option>
                    <option value="critical" {{ $currentPriority === 'critical' ? 'selected' : '' }}>Critical</option>
                </select>
            </div>

            {{-- Due Date --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" name="due_date"
                       value="{{ old('due_date', isset($task->dueDate) ? $task->dueDate->format('Y-m-d') : '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>

            @if(isset($task) && $task->completedAt)
            {{-- Completed At (read-only, shown on edit) --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Completed At</label>
                <input type="text" readonly
                       value="{{ $task->completedAt->format('d/m/Y H:i') }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
            </div>
            @endif

            {{-- Description (full width) --}}
            <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="4"
                          placeholder="Describe what needs to be done..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none">{{ old('description', $task->description ?? '') }}</textarea>
            </div>

        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M15 12v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h3M10 2h6v6M8 10l8-8"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ isset($task) ? 'Update Task' : 'Save Task' }}
            </button>
            <a href="{{ route('task.index') }}"
               class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                Cancel
            </a>
        </div>
    </form>

</main>
@endsection
