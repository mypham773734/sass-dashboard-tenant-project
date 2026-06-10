@props(['tenantId', 'active'])

@php
$items = [
    'email'         => ['label' => 'Email',         'icon' => 'fa-envelope'],
    'notifications' => ['label' => 'Notifications', 'icon' => 'fa-bell'],
    'localization'  => ['label' => 'Localization',  'icon' => 'fa-globe'],
    'members'       => ['label' => 'Members',       'icon' => 'fa-users'],
];
@endphp

<nav class="md:w-56 shrink-0 space-y-1">
    @foreach ($items as $key => $item)
        <a href="{{ route('tenant.settings.index', [$tenantId, $key]) }}"
           @class([
               'flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium transition-all',
               'bg-indigo-50 text-indigo-600' => $active === $key,
               'text-gray-600 hover:bg-gray-50' => $active !== $key,
           ])>
            <i class="fas {{ $item['icon'] }} w-4"></i>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
