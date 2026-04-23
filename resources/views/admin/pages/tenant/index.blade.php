@extends('admin.layouts.app');

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">
    <!-- List Table Page -->
    <div id="list-page" class="fade-in space-y-6">
        <!-- Header + Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Quản lý danh sách Tenant</h3>
                <p class="text-gray-500 mt-1">Quản lý và cấu hình tất cả các đơn vị (tenant) trong hệ thống. Theo dõi trạng thái hoạt động, thông tin gói dịch vụ và tài nguyên riêng biệt của từng tổ chức.</p>
            </div>
            <a href="{{ route('tenant.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors whitespace-nowrap font-medium">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                Thêm mới
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Tìm kiếm</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5" />
                            <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        <input id="search-input" type="text" placeholder="Tên, slug..." oninput="applyFilters()" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Trạng thái</label>
                    <select id="status-filter" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="hidden">
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Vai trò</label>
                    <select id="role-filter" onchange="applyFilters()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                        <option value="">Tất cả</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button onclick="clearFilters()" class="w-full px-4 py-2 text-gray-600 bg-indigo-600 text-white border border-gray-300 rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                        Filter
                    </button>
                    <button onclick="clearFilters()" class="w-full px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                        Xóa bộ lọc
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Trial Ends At</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày tạo</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="data-table-body" class="divide-y divide-gray-200">
                        @if(isset($tenants) && $tenants->count() > 0)
                        @foreach($tenants as $tenant)
                        <tr>
                            <td class="px-6 py-3 text-left text-xs text-gray-600 uppercase tracking-wider">{{ $tenant->id }}</td>
                            <td class="px-6 py-3 text-left text-xs text-gray-600 uppercase tracking-wider">{{ $tenant->name }}</td>
                            <td class="px-6 py-3 text-left text-xs text-gray-600 uppercase tracking-wider">{{ $tenant->slug }}</td>
                            <td class="px-6 py-3 text-left text-xs text-gray-600 uppercase tracking-wider">{{ $tenant->is_active }}</td>
                            <td class="px-6 py-3 text-left text-xs text-gray-600 uppercase tracking-wider">{{ $tenant->trial_ends_at }}</td>
                            <td class="px-6 py-3 text-left text-xs text-gray-600 uppercase tracking-wider">{{ $tenant->created_at }}</td>
                            <td class="px-6 py-3 text-right text-xs text-gray-600 uppercase tracking-wider flex justify-end gap-2">
                                <a href="{{route('tenant.edit', $tenant->slug)}}" class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition h-fit">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Nút Xóa -->
                                <form action="{{ route('tenant.destroy', $tenant->slug) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- Empty State -->
            @if(!isset($tenants) || $tenants->count() < 1)
                <div id="empty-state" class="hidden py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Không tìm thấy dữ liệu</h3>
                <p class="mt-1 text-sm text-gray-500">Thử thay đổi bộ lọc hoặc thêm mục mới.</p>
        </div>
        @endif
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Hiển thị <span id="showing-count">{{ $tenants->firstItem() }}-{{ $tenants->lastItem() }}</span> trên tổng <span id="total-count">{{ $tenants->total() }}</span>
            </div>
            {{-- <div class="flex items-center gap-2">
                <button disabled class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-400 bg-gray-50 cursor-not-allowed">Trước</button>
                <button class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">1</button>
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">2</button>
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">3</button>
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Sau</button>
            </div> --}}

            {{ $tenants->links() }}
        </div>
    </div>
    </div>

</main>

<x-modal></x-modal>
@endsection