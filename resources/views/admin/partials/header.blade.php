<header class="bg-white/90 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-20 px-6 py-4 flex items-center justify-between shadow-sm">
    <!-- Left: page title & breadcrumb -->
    <div class="flex items-center gap-4">
        <div class="lg:hidden">
            <button id="mobileMenuBtn" class="text-slate-600 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Dashboard</h2>
            <div class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                <span>Home</span>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="font-medium text-indigo-600">Overview</span>
            </div>
        </div>
    </div>

    <!-- Right: search & notifications & avatar -->
    <div class="flex items-center gap-5">
        @if(session('message'))
        <p>{{ session('message') }}</p>
        @endif
        <livewire:tenant-switcher />
        <!-- Search bar (desktop) -->
        <div class="hidden md:flex items-center bg-slate-50 rounded-full px-4 py-2 gap-2 border border-slate-200">
            <i class="fas fa-search text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search projects..." class="bg-transparent text-sm border-none outline-none w-48 placeholder:text-slate-400 focus:shadow-none">
        </div>
        <!-- Notification bell -->
        <button class="relative text-slate-500 hover:text-indigo-600 transition">
            <i class="far fa-bell text-xl"></i>
            <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold">3</span>
        </button>

        <!-- Dropdown action menu -->
        <!-- <div class="relative">
            <button id="dropdownMenuBtn" class="flex items-center gap-2 border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition rounded-full px-3 py-2 text-sm font-medium">
                Tenant
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-2xl shadow-xl py-2 z-30">
                <a href="#" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Add Tenant</a>
                <a href="#" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">All Tenants</a>
            </div>
        </div> -->

        <!-- Avatar small for mobile/desktop -->
        <!-- User profile bottom sidebar -->
    @php
        $authUser  = auth()->user();
        $initials  = strtoupper(substr($authUser->name, 0, 1))
                   . strtoupper(substr(strrchr($authUser->name, ' ') ?: ' x', 1, 1));
        $avatarVal = $authUser->avatar;
    @endphp
        {{-- <div class="w-9 h-9 min-w-9 rounded-full bg-gradient-to-tr from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-semibold text-sm shadow">
            JD
        </div> --}}

        <div class="p-4 border-t border-slate-100 mt-auto">
        <a href="{{ route('profile.show') }}" class="flex items-center gap-3 p-2 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
            @if($avatarVal)
                <img src="{{ asset('storage/' . $avatarVal) }}"
                     class="w-10 h-10 rounded-full object-cover shadow"
                     alt="avatar">
            @else
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow">
                    {{ $initials }}
                </div>
            @endif
        </a>
    </div>
    </div>
</header>