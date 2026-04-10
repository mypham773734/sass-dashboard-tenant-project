@extends('admin.layouts.app');

@push('script')
<!-- Chart.js for simple analytics chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('scripts')
@vite('resources/js/pages/dashboard.js')
@endpush

@section('content')
<main class="flex-1 overflow-y-auto p-5 md:p-7 bg-gradient-to-b from-slate-50 to-white">

    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-5 md:p-6 text-white mb-7 shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h3 class="text-xl font-bold">Chào mừng trở lại, John! 👋</h3>
                <p class="text-indigo-100 text-sm mt-1">Hôm nay là một ngày tuyệt vời để quản lý dự án. Bạn có 3 nhiệm vụ cần hoàn thành.</p>
            </div>
            <div class="mt-3 md:mt-0 bg-white/20 rounded-xl px-4 py-2 backdrop-blur-sm">
                <span class="text-sm font-semibold"><i class="far fa-clock mr-1"></i> Tuần này: +24% tiến độ</span>
            </div>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-100 transition-all hover:shadow-md">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Dự án đang thực hiện</p>
                    <h4 class="text-2xl font-bold text-slate-800 mt-1">12</h4>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center">
                    <i class="fas fa-folder-open text-indigo-600"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-emerald-600">
                <i class="fas fa-arrow-up mr-1"></i> +2 từ tuần trước
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Thành viên</p>
                    <h4 class="text-2xl font-bold text-slate-800 mt-1">8</h4>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-users text-emerald-600"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-slate-500">
                <i class="fas fa-user-plus mr-1"></i> 2 người mới
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Hoàn thành</p>
                    <h4 class="text-2xl font-bold text-slate-800 mt-1">47</h4>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-check-circle text-amber-600"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-emerald-600">
                <i class="fas fa-arrow-up mr-1"></i> +15% hiệu suất
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Deadline sắp tới</p>
                    <h4 class="text-2xl font-bold text-slate-800 mt-1">5</h4>
                </div>
                <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center">
                    <i class="fas fa-hourglass-half text-rose-600"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-rose-600">
                <i class="fas fa-exclamation-triangle mr-1"></i> Cần chú ý
            </div>
        </div>
    </div>

    <!-- Charts & Recent Activity (2 columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-7 mb-8">
        <!-- Chart Card -->
        <div class="bg-white rounded-2xl shadow-card border border-slate-100 p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-700"><i class="fas fa-chart-line text-indigo-500 mr-2"></i> Tiến độ dự án theo tuần</h3>
                <select class="text-xs border rounded-lg px-2 py-1 bg-slate-50">
                    <option>6 tháng qua</option>
                    <option>Năm nay</option>
                </select>
            </div>
            <canvas id="progressChart" width="400" height="200" style="max-height: 240px;"></canvas>
        </div>

        <!-- Recent Tasks / Activity -->
        <div class="bg-white rounded-2xl shadow-card border border-slate-100 p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-700"><i class="fas fa-list-check text-indigo-500 mr-2"></i> Nhiệm vụ gần đây</h3>
                <a href="#" class="text-xs text-indigo-600 font-medium hover:underline">Xem tất cả <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="text-sm text-slate-700">Thiết kế giao diện Dashboard</span>
                    </div>
                    <span class="text-xs text-slate-400">2 ngày trước</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                        <span class="text-sm text-slate-700">Tích hợp API thanh toán</span>
                    </div>
                    <span class="text-xs text-slate-400">Hôm qua</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        <span class="text-sm text-slate-700">Viết tài liệu kỹ thuật</span>
                    </div>
                    <span class="text-xs text-slate-400">3 giờ trước</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <span class="text-sm text-slate-700">Review code module user</span>
                    </div>
                    <span class="text-xs text-slate-400">1 ngày trước</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                        <span class="text-sm text-slate-700">Fix bug responsive mobile</span>
                    </div>
                    <span class="text-xs text-slate-400">5 giờ trước</span>
                </div>
            </div>
            <!-- Progress summary -->
            <div class="mt-4 pt-3 border-t border-slate-100">
                <div class="flex justify-between text-xs mb-1"><span>Hoàn thành tuần này</span><span class="font-semibold">68%</span></div>
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: 68%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-2xl shadow-card border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap justify-between items-center">
            <h3 class="font-bold text-slate-700"><i class="fas fa-rocket text-indigo-500 mr-2"></i> Dự án nổi bật</h3>
            <button class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-full font-medium hover:bg-indigo-100 transition"><i class="fas fa-plus mr-1"></i> Tạo dự án</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs font-semibold">
                    <tr>
                        <th class="px-5 py-3">Tên dự án</th>
                        <th class="px-5 py-3">Trưởng nhóm</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3">Tiến độ</th>
                        <th class="px-5 py-3">Hạn chót</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-5 py-3 font-medium text-slate-700">FlowSaaS Web App</td>
                        <td class="px-5 py-3 text-slate-600">John Doe</td>
                        <td class="px-5 py-3"><span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-1 rounded-full">Đang tiến hành</span></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2"><span class="text-xs">74%</span>
                                <div class="w-20 bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width:74%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">15/12/2025</td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-5 py-3 font-medium text-slate-700">Mobile App iOS</td>
                        <td class="px-5 py-3 text-slate-600">Sarah Lee</td>
                        <td class="px-5 py-3"><span class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded-full">Review</span></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2"><span class="text-xs">92%</span>
                                <div class="w-20 bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-amber-500 h-1.5 rounded-full" style="width:92%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">05/01/2026</td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-5 py-3 font-medium text-slate-700">Landing Page Redesign</td>
                        <td class="px-5 py-3 text-slate-600">Mike Tran</td>
                        <td class="px-5 py-3"><span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">Lên kế hoạch</span></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2"><span class="text-xs">30%</span>
                                <div class="w-20 bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width:30%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">28/02/2026</td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-5 py-3 font-medium text-slate-700">Tích hợp AI Chatbot</td>
                        <td class="px-5 py-3 text-slate-600">Anna Pham</td>
                        <td class="px-5 py-3"><span class="bg-rose-100 text-rose-700 text-xs px-2 py-1 rounded-full">Khẩn cấp</span></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2"><span class="text-xs">45%</span>
                                <div class="w-20 bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-rose-500 h-1.5 rounded-full" style="width:45%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-500">10/12/2025</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection

