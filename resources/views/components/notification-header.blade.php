@props(['unreadCount' => 0])

<div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
    <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
    @if ($unreadCount > 0)
        <button
            wire:click="markAllAsRead"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium transition"
        >
            Mark all as read
        </button>
    @endif
</div>
