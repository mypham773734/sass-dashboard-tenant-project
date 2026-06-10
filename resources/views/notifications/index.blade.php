@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            {{-- Header --}}
            <div class="px-6 py-4 bg-white border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900">Notifications</h2>
                @if ($notifications->count() > 0)
                    <a
                        href="{{ route('notifications.mark-all-read') }}"
                        method="POST"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"
                    >
                        Mark All as Read
                    </a>
                @endif
            </div>

            {{-- Notifications List --}}
            <div class="divide-y divide-gray-200">
                @forelse ($notifications as $notification)
                    <div class="px-6 py-4 hover:bg-gray-50 transition {{ !$notification->is_read ? 'bg-blue-50' : '' }}">
                        <div class="flex items-start gap-4">
                            {{-- Unread Indicator --}}
                            <div class="flex-shrink-0 pt-1">
                                <div class="w-3 h-3 rounded-full {{ !$notification->is_read ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <a
                                            href="{{ $notification->url }}"
                                            class="text-lg font-medium text-gray-900 hover:text-blue-600 break-words block"
                                        >
                                            {{ $notification->title }}
                                        </a>

                                        @if ($notification->body)
                                            <p class="text-gray-600 mt-2">{{ $notification->body }}</p>
                                        @endif

                                        <div class="flex items-center gap-4 mt-2">
                                            <p class="text-sm text-gray-500">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                            @if (!$notification->is_read)
                                                <a
                                                    href="{{ route('notifications.mark-read', $notification->id) }}"
                                                    method="POST"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition"
                                                >
                                                    Mark as read
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    <span class="text-xs font-medium px-2 py-1 bg-gray-100 text-gray-700 rounded whitespace-nowrap">
                                        {{ $notification->event }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Empty State --}}
                    <div class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="text-lg text-gray-500">No notifications</p>
                        <p class="text-sm text-gray-400 mt-1">You'll see notifications here when things happen</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($notifications->count() > 0)
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
