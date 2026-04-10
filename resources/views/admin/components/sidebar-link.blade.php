@props(['route'])

@php
    $isActive = $route && request()->routeIs($route . '*');
    $link = Route::has($route) ? route($route) : '#'; 
@endphp


<a href="{{ $link }}"
    @class([
        'sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl font-medium transition-all duration-150 hover:bg-slate-50', 
        'active text-slate-600' => $isActive, 
        'text-slate-500' => !$isActive
    ])>
    {{ $slot }}
</a>