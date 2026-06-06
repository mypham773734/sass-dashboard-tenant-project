@extends('admin.layouts.app')

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    {{-- Header --}}
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-900">My Profile</h3>
        <p class="text-gray-500 mt-1">Manage your personal information and account settings.</p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column: Avatar + Basic Info --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Section 1: Basic Info --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="text-base font-semibold text-gray-900">Basic Information</h4>
                    <p class="text-xs text-gray-500 mt-0.5">Update your name, email and contact number.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="p-6">
                    @csrf

                    {{-- Avatar upload --}}
                    <div class="flex items-center gap-5 mb-6">
                        <div class="relative group">
                            @if($profile->avatarUrl)
                                <img src="{{ $profile->avatarUrl }}"
                                     id="avatar-preview"
                                     class="w-20 h-20 rounded-full object-cover border-2 border-indigo-100 shadow"
                                     alt="Avatar">
                            @else
                                <div id="avatar-initials"
                                     class="w-20 h-20 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow">
                                    {{ strtoupper(substr($profile->name, 0, 1)) }}{{ strtoupper(substr(strrchr($profile->name, ' ') ?: ' x', 1, 1)) }}
                                </div>
                            @endif
                            <label for="avatar-input"
                                   class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center cursor-pointer transition-opacity">
                                <i class="fas fa-camera text-white text-lg"></i>
                            </label>
                        </div>
                        <div>
                            <label for="avatar-input" class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Change photo
                            </label>
                            <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" class="hidden">
                            <p class="text-xs text-gray-400 mt-1">JPEG, PNG or WEBP · max 2 MB</p>
                            @error('avatar')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                        <input type="text" name="name"
                               value="{{ old('name', $profile->name) }}"
                               class="w-full px-3 py-2 border @error('name') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" name="email"
                               value="{{ old('email', $profile->email) }}"
                               class="w-full px-3 py-2 border @error('email') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        @error('email')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-amber-600 mt-1">
                            <i class="fas fa-exclamation-triangle"></i>
                            Make sure your email is correct before saving.
                        </p>
                    </div>

                    {{-- Phone --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" name="phone"
                               value="{{ old('phone', $profile->phone) }}"
                               placeholder="+84 901 234 567"
                               class="w-full px-3 py-2 border @error('phone') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        @error('phone')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- Section 2: Change Password --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="text-base font-semibold text-gray-900">Change Password</h4>
                    <p class="text-xs text-gray-500 mt-0.5">Use a strong password with at least 8 characters.</p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="p-6">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                        <input type="password" name="current_password"
                               class="w-full px-3 py-2 border @error('current_password') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               autocomplete="current-password">
                        @error('current_password')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="new_password"
                               class="w-full px-3 py-2 border @error('new_password') border-red-400 @else border-gray-300 @enderror rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               autocomplete="new-password">
                        @error('new_password')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               autocomplete="new-password">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-5 py-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg shadow transition-colors">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

        </div>

        {{-- Right column: Tenant Memberships --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="text-base font-semibold text-gray-900">Tenant Memberships</h4>
                    <p class="text-xs text-gray-500 mt-0.5">Workspaces you belong to.</p>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($profile->tenants as $tenant)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-semibold text-gray-800">{{ $tenant['name'] }}</p>
                                <span class="px-2 py-0.5 text-xs rounded-full
                                    @if(in_array($tenant['role'], ['owner', 'admin']))
                                        bg-indigo-100 text-indigo-700
                                    @else
                                        bg-slate-100 text-slate-600
                                    @endif font-medium">
                                    {{ ucfirst($tenant['role']) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mb-3">{{ $tenant['slug'] }}</p>

                            @if(session('current_tenant_id') == $tenant['id'])
                                <span class="text-xs text-green-600 font-medium">
                                    <i class="fas fa-check-circle"></i> Active workspace
                                </span>
                            @else
                                <form method="POST" action="{{ route('tenant.switch', $tenant['id']) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        Switch →
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-400">
                            <i class="fas fa-building text-2xl mb-2 block"></i>
                            No tenant memberships yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</main>

{{-- Avatar preview script --}}
<script>
document.getElementById('avatar-input')?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (ev) {
        let preview = document.getElementById('avatar-preview');
        const initials = document.getElementById('avatar-initials');

        if (!preview) {
            preview = document.createElement('img');
            preview.id = 'avatar-preview';
            preview.className = 'w-20 h-20 rounded-full object-cover border-2 border-indigo-100 shadow';
            preview.alt = 'Avatar';
            if (initials) initials.replaceWith(preview);
        }

        preview.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
