<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FlowSaaS | Project Dashboard</title>
    <!-- Tailwind CSS v3 + Font Awesome + Google Fonts (Inter) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js for simple analytics chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        dark: {
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    },
                    boxShadow: {
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02)',
                        'sidebar': '4px 0 20px -8px rgba(0, 0, 0, 0.1)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom scrollbar for sidebar */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        body {
            background: #f8fafc;
        }

        /* active menu style */
        .sidebar-link.active {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(67, 56, 202, 0.05));
            color: #4f46e5;
            border-right: 3px solid #4f46e5;
        }

        .sidebar-link:hover:not(.active) {
            background: #f1f5f9;
            color: #334155;
        }
    </style>
</head>

<body class="font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <!-- ======================= SIDEBAR ======================= -->
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
                <a href="#" class="sidebar-link active flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-600 font-medium transition-all duration-150">
                    <i class="fas fa-tachometer-alt w-5 text-indigo-500"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-500 font-medium transition-all duration-150 hover:bg-slate-50">
                    <i class="fas fa-project-diagram w-5"></i>
                    <span>Projects</span>
                </a>
                <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-500 font-medium transition-all duration-150 hover:bg-slate-50">
                    <i class="fas fa-tasks w-5"></i>
                    <span>Tasks</span>
                </a>
                <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-500 font-medium transition-all duration-150 hover:bg-slate-50">
                    <i class="fas fa-users w-5"></i>
                    <span>Team</span>
                </a>
                <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-500 font-medium transition-all duration-150 hover:bg-slate-50">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span>Calendar</span>
                </a>
                <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-500 font-medium transition-all duration-150 hover:bg-slate-50">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>Analytics</span>
                </a>
                <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2.5 rounded-xl text-slate-500 font-medium transition-all duration-150 hover:bg-slate-50">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
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

        <!-- ======================= MAIN CONTENT (RIGHT SIDE) ======================= -->
        <div class="flex-1 ml-72 flex flex-col h-screen overflow-hidden">

            <!-- ======================= HEADER ======================= -->
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
                    <!-- Search bar (desktop) -->
                    <div class="hidden md:flex items-center bg-slate-50 rounded-full px-4 py-2 gap-2 border border-slate-200">
                        <i class="fas fa-search text-slate-400 text-sm"></i>
                        <input type="text" placeholder="Search projects..." class="bg-transparent text-sm outline-none w-48 placeholder:text-slate-400">
                    </div>
                    <!-- Notification bell -->
                    <button class="relative text-slate-500 hover:text-indigo-600 transition">
                        <i class="far fa-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold">3</span>
                    </button>
                    <!-- Avatar small for mobile/desktop -->
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-semibold text-sm shadow">
                        JD
                    </div>
                </div>
            </header>

            <!-- ======================= MAIN SCROLLABLE CONTENT ======================= -->
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

            <!-- ======================= FOOTER ======================= -->
            <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-400 flex flex-wrap justify-between items-center">
                <div>© 2025 FlowSaaS - Project Management Platform. All rights reserved.</div>
                <div class="flex gap-4 mt-1 sm:mt-0">
                    <a href="#" class="hover:text-indigo-600 transition">Privacy</a>
                    <a href="#" class="hover:text-indigo-600 transition">Terms</a>
                    <a href="#" class="hover:text-indigo-600 transition">Support</a>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Chart initialization
        const ctx = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4', 'Tuần 5', 'Tuần 6'],
                datasets: [{
                    label: 'Tiến độ %',
                    data: [30, 45, 55, 70, 82, 88],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.05)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#f1f5f9'
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: '#e2e8f0'
                        },
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Hoàn thành (%)',
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Tuần',
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Mobile menu toggle (for responsive)
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.querySelector('aside');
        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
            // close when clicking outside on mobile (optional)
            document.addEventListener('click', function(event) {
                const isClickInside = sidebar.contains(event.target) || mobileBtn.contains(event.target);
                if (!isClickInside && window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        }
        // Ensure on window resize if screen becomes large, reset sidebar position
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
            } else {
                if (!sidebar.classList.contains('-translate-x-full') && !sidebar.style.transform) {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });
        // initial state for mobile
        if (window.innerWidth < 1024) {
            sidebar.classList.add('-translate-x-full');
        } else {
            sidebar.classList.remove('-translate-x-full');
        }
    </script>
</body>

</html>