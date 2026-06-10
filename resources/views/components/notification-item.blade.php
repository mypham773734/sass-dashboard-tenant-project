@props(['notification', 'clickable' => false, 'onMarkRead' => null])

<div
    @if($clickable)
        wire:click="{{ $onMarkRead ?? "markRead({$notification['id']})" }}"
        class="cursor-pointer"
    @endif
    class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition {{ !$notification['isRead'] ? 'bg-blue-50' : '' }}"
>
    <div class="flex items-start gap-3">
        <!-- Unread Indicator -->
        <div class="flex-shrink-0 pt-1">
            @if (!$notification['isRead'])
                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
            @else
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
            @endif
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <a
                href="{{ $notification['url'] ?? '#' }}"
                class="text-sm font-medium text-gray-900 hover:text-blue-600 break-words"
            >
                {{ $notification['title'] }}
            </a>

            @if (isset($notification['body']) && $notification['body'])
                <p class="text-xs text-gray-500 mt-1 break-words">
                    {{ $notification['body'] }}
                </p>
            @endif

            <p class="text-xs text-gray-400 mt-1">
                {{ isset($notification['createdAt']) ? \Carbon\Carbon::parse($notification['createdAt'])->diffForHumans() : '' }}
            </p>
        </div>
    </div>
</div>
