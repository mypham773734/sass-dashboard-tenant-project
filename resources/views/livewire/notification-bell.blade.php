<div class="relative">
    {{-- Bell Button with Badge --}}
    <button
        wire:click="toggleDropdown"
        class="relative inline-flex items-center justify-center w-10 h-10 text-gray-600 hover:text-gray-900 focus:outline-none transition"
        title="Notifications"
        aria-label="Open notifications"
    >
        <x-notification-bell-icon :count="$unreadCount" />
    </button>

    {{-- Dropdown Menu --}}
    @if ($isOpen)
        <div class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 max-h-96 overflow-hidden flex flex-col">
            {{-- Header --}}
            <x-notification-header :unread-count="$unreadCount" />

            {{-- Notifications List --}}
            <div class="overflow-y-auto flex-1">
                @forelse ($notifications as $notification)
                    <x-notification-item :notification="$notification" clickable="true" />
                @empty
                    <x-notification-empty />
                @endforelse
            </div>

            {{-- Footer --}}
            @if (count($notifications) > 0)
                <x-notification-footer />
            @endif
        </div>

        {{-- Click Outside to Close --}}
        <div wire:click="toggleDropdown" class="fixed inset-0 z-40"></div>
    @endif
</div>
