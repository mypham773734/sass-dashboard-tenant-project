@extends('admin.layouts.app');

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">
    <!-- Create New Page -->
    <div id="create-page" class="fade-in">
        <div class="flex items-center gap-4 mb-6">
            <button onclick="showListPage()" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15 10H5M5 10l4-4M5 10l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Thêm người dùng mới</h3>
                <p class="text-gray-500 mt-1">Tạo tài khoản cho người dùng mới trong hệ thống</p>
            </div>
        </div>

        <form id="create-form" onsubmit="handleCreateSubmit(event)" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Họ tên <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="Nguyễn Văn A" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required placeholder="nguyenvana@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Số điện thoại</label>
                    <input type="tel" name="phone" placeholder="0912345678" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Vai trò <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="">Chọn vai trò</option>
                        <option value="user">Người dùng</option>
                        <option value="manager">Quản lý</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Trạng thái <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                        <option value="">Chọn trạng thái</option>
                        <option value="active">Hoạt động</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="inactive">Không hoạt động</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                <textarea name="notes" rows="3" placeholder="Thông tin thêm về người dùng..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"></textarea>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M15 12v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h3M10 2h6v6M8 10l8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Lưu
                </button>
                <button type="button" onclick="showListPage()" class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    Hủy
                </button>
            </div>
        </form>
    </div>

</main>
@endsection