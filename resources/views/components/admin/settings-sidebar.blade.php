@props([
    'tenantId',
    'active' => 'email',
])

@php
    $sections = [
        'email' => ['label' => 'Email Notifications', 'icon' => 'fa-envelope'],
        'notifications' => ['label' => 'Notifications', 'icon' => 'fa-bell'],
        'localization' => ['label' => 'Localization', 'icon' => 'fa-globe'],
        'members' => ['label' => 'Members', 'icon' => 'fa-users'],
    ];
@endphp

<aside class="w-full md:w-64 shrink-0">
    <nav class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <ul class="divide-y divide-gray-100">
            @foreach ($sections as $key => $section)
                <li>
                    <a href="{{ route('tenant.settings.index', [$tenantId, $key]) }}"
                       class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors {{ $active === $key
                                  ? 'bg-indigo-50 text-indigo-600 border-l-4 border-indigo-600'
                                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent' }}">
                        <i class="fa-solid {{ $section['icon'] }} w-4 text-center"></i>
                        {{ $section['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
