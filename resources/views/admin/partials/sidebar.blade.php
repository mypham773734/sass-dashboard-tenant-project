<aside class="fixed inset-y-0 left-0 z-30 w-72 bg-white border-r border-slate-200 shadow-sidebar flex flex-col transition-all duration-300 overflow-y-auto">
    <!-- Logo area -->
    <div class="flex items-center gap-3 px-6 py-6 border-b border-slate-100">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 flex items-center justify-center shadow-md">
            <i class="fas fa-chart-line text-white text-xl"></i>
        </div>
        <div>
            <h1 class="text-xl font-extrabold bg-gradient-to-r from-slate-800 to-indigo-800 bg-clip-text text-transparent">FlowSaaS</h1>
            <p class="text-[11px] text-slate-400 font-medium -mt-0.5">Project Management</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-1">
        <!-- Dashboard -->
        <x-sidebar-link route="dashboard">
            <i class="fas fa-tachometer-alt w-5 text-indigo-500"></i>
            <span>Dashboard</span>
        </x-sidebar-link>

        <!-- Tenant -->
        <x-sidebar-link route="tenant.index">
            <i class="fa-solid fa-landmark"></i>
            <span>Tenant</span>
        </x-sidebar-link>

        <!-- Project -->
        <x-sidebar-link route="#">
            <i class="fas fa-project-diagram w-5"></i>
            <span>Projects</span>
        </x-sidebar-link>

        <!-- Tasks -->
        <x-sidebar-link route="#">
            <i class="fas fa-tasks w-5"></i>
            <span>Tasks</span>
        </x-sidebar-link>

        <!-- Team -->
        <x-sidebar-link route="#">
            <i class="fas fa-users w-5"></i>
            <span>Team</span>
        </x-sidebar-link>

        <!-- Calendar -->
        <x-sidebar-link route="#">
            <i class="fas fa-calendar-alt w-5"></i>
            <span>Calendar</span>
        </x-sidebar-link>

        <!-- Analytics -->
        <x-sidebar-link route="#">
            <i class="fas fa-chart-pie w-5"></i>
            <span>Analytics</span>
        </x-sidebar-link>

        <!-- Settings -->
        <x-sidebar-link route="#">
            <i class="fas fa-cog w-5"></i>
            <span>Settings</span>
        </x-sidebar-link>
    </nav>

    <!-- User profile bottom sidebar -->
    <div class="p-4 border-t border-slate-100 mt-auto">
        <div class="flex items-center gap-3 p-2 rounded-xl bg-slate-50">
            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow">
                JD
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-700 truncate">John Doe</p>
                <p class="text-xs text-slate-400 truncate">john@flowsaas.com</p>
            </div>
            <i class="fas fa-chevron-right text-slate-400 text-xs"></i>
        </div>
    </div>
</aside>