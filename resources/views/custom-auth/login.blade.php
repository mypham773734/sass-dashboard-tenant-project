<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Sign In | SaaS Dashboard</title>
  <!-- Tailwind CSS v3 + Font Awesome Icons + Google Fonts (Inter) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Ẩn eye toggle mặc định nếu cần, nhưng Tailwind xử lý hầu hết, chỉ thêm vài custom animation */
    .toggle-pwd-btn {
      cursor: pointer;
      transition: color 0.2s;
    }

    .toggle-pwd-btn:hover {
      color: #4f46e5;
    }

    @keyframes fadeSlideUp {
      from {
        opacity: 0;
        transform: translateY(16px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .animate-fade-slide {
      animation: fadeSlideUp 0.4s ease-out;
    }

    /* Custom focus ring cho input để khớp với primary color */
    input:focus {
      --tw-ring-color: #4f46e5;
      --tw-ring-offset-width: 0px;
    }
  </style>
</head>

<body class="bg-gradient-to-br from-slate-50 via-indigo-50/40 to-blue-50 font-sans antialiased min-h-screen flex items-center justify-center p-5">

  <!-- Main Card Container - dùng Tailwind hoàn toàn -->
  <div class="w-full max-w-md animate-fade-slide">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-card overflow-hidden transition-all duration-300">
      <div class="p-6 sm:p-8 md:p-9">

        <!-- Brand Header -->
        <div class="text-center mb-7">
          <div class="inline-flex items-center justify-center bg-gradient-to-br from-indigo-700 to-indigo-900 w-14 h-14 rounded-2xl shadow-md mb-4">
            <i class="fas fa-chart-line text-white text-2xl"></i>
          </div>
          <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-indigo-800 bg-clip-text text-transparent">FlowSaaS</h1>
          <p class="text-slate-500 text-sm font-medium mt-1.5">Smart dashboard · insights at glance</p>
        </div>

        <!-- Error Message Container (ẩn mặc định) -->
        <div id="errorBox" class="hidden mb-5 bg-red-50 border-l-4 border-red-500 rounded-xl p-3 flex items-center gap-2 text-red-700 text-sm font-medium">
          <i class="fas fa-exclamation-circle text-red-500"></i>
          <span id="errorText">Invalid credentials</span>
        </div>

        <!-- Login Form -->
        <form id="loginForm" action="{{ route('custom-login.store') }}" method="POST" class="space-y-5">
          @csrf
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-slate-700 font-semibold text-sm mb-1.5">Email address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-slate-400 text-sm"></i>
              </div>
              <input type="email" id="email" name="email" value="demo@flowsaas.com"
                class="w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-all text-slate-800 text-sm"
                placeholder="admin@flowsaas.com" autocomplete="email">
            </div>
          </div>

          <!-- Password Field with Toggle -->
          <div>
            <div class="flex justify-between items-center mb-1.5">
              <label for="password" class="text-slate-700 font-semibold text-sm">Password</label>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-slate-400 text-sm"></i>
              </div>
              <input type="password" id="password" name="password" value="demo123"
                class="w-full pl-10 pr-10 py-2.5 border border-slate-200 rounded-xl bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-all text-slate-800 text-sm"
                placeholder="••••••••" autocomplete="current-password">
              <button type="button" id="togglePasswordBtn" class="toggle-pwd-btn absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 transition">
                <i id="toggleIcon" class="far fa-eye-slash text-sm"></i>
              </button>
            </div>
          </div>

          <!-- Options Row: Remember + Forgot -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
              <input type="checkbox" id="rememberCheckbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
              <span>Remember me</span>
            </label>
            <a href="#" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 transition hover:underline">Forgot password?</a>
          </div>

          <!-- Login Button -->
          <button type="submit" id="loginBtn" class="w-full bg-gradient-to-r from-indigo-700 to-indigo-800 hover:from-indigo-800 hover:to-indigo-900 text-white font-bold py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 text-base">
            <i class="fas fa-arrow-right-to-bracket text-sm"></i>
            Sign in
          </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center my-6">
          <div class="flex-1 border-t border-slate-200"></div>
          <span class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">or continue with</span>
          <div class="flex-1 border-t border-slate-200"></div>
        </div>

        <!-- Mock Social Login (UI only) -->
        <div class="flex justify-center gap-3 mb-5">
          <div class="bg-slate-50 border border-slate-200 rounded-full px-4 py-2 text-sm font-medium text-slate-700 flex items-center gap-2 cursor-default">
            <i class="fab fa-google text-indigo-500"></i> Google
          </div>
          <div class="bg-slate-50 border border-slate-200 rounded-full px-4 py-2 text-sm font-medium text-slate-700 flex items-center gap-2 cursor-default">
            <i class="fab fa-github text-slate-700"></i> GitHub
          </div>
        </div>

        <!-- Signup Link -->
        <div class="text-center text-sm text-slate-500">
          Don't have an account?
          <a href="#" class="font-bold text-indigo-700 hover:text-indigo-900 hover:underline">Start free trial</a>
        </div>
        <div class="text-center text-xs text-slate-400 mt-3 flex items-center justify-center gap-1">
          <i class="fas fa-shield-alt text-indigo-400"></i> Secure login — end‑to‑end encrypted
        </div>
      </div>
    </div>
  </div>

  <script>
    (function() {
      return;
      // DOM elements
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');
      const toggleBtn = document.getElementById('togglePasswordBtn');
      const toggleIcon = document.getElementById('toggleIcon');
      const loginForm = document.getElementById('loginForm');
      const errorBox = document.getElementById('errorBox');
      const errorTextSpan = document.getElementById('errorText');
      const rememberCheckbox = document.getElementById('rememberCheckbox');
      const loginButton = document.getElementById('loginBtn');

      // Helper hiển thị lỗi
      function showError(message) {
        errorTextSpan.innerText = message;
        errorBox.classList.remove('hidden');
        // Tự động ẩn sau 4 giây
        setTimeout(() => {
          errorBox.classList.add('hidden');
        }, 4000);
      }

      function clearError() {
        errorBox.classList.add('hidden');
      }

      // Validate email cơ bản
      function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        return emailRegex.test(email);
      }

      // Hàm xác thực (giả lập backend)
      function authenticateUser(email, password) {
        // Demo credentials mẫu cho SaaS dashboard
        const validAccounts = [{
            email: "demo@flowsaas.com",
            password: "demo123"
          },
          {
            email: "admin@flowsaas.com",
            password: "admin123"
          },
          {
            email: "user@flowsaas.com",
            password: "password"
          }
        ];
        const matched = validAccounts.find(acc => acc.email === email && acc.password === password);
        if (matched) {
          return {
            success: true,
            message: "Đăng nhập thành công"
          };
        }
        // Hỗ trợ thêm: nếu email kết thúc bằng @flowsaas.com và password có ít nhất 4 ký tự
        if (email.endsWith("@flowsaas.com") && password && password.trim().length >= 4) {
          return {
            success: true,
            message: "Chào mừng đến với FlowSaaS"
          };
        }
        return {
          success: false,
          message: "Email hoặc mật khẩu không đúng. Thử demo@flowsaas.com / demo123"
        };
      }

      // Lưu session với Remember Me
      function saveSession(email, remember) {
        if (remember) {
          localStorage.setItem('saas_remember_email', email);
          localStorage.setItem('saas_remember_flag', 'true');
        } else {
          localStorage.removeItem('saas_remember_email');
          localStorage.setItem('saas_remember_flag', 'false');
        }
        sessionStorage.setItem('saas_auth_email', email);
        sessionStorage.setItem('saas_logged_in', 'true');
      }

      // Load lại email đã ghi nhớ
      function loadRememberedEmail() {
        const rememberFlag = localStorage.getItem('saas_remember_flag');
        const savedEmail = localStorage.getItem('saas_remember_email');
        if (rememberFlag === 'true' && savedEmail) {
          emailInput.value = savedEmail;
          rememberCheckbox.checked = true;
        } else {
          rememberCheckbox.checked = false;
        }
      }

      // Toggle hiển thị mật khẩu
      function setupPasswordToggle() {
        if (toggleBtn) {
          toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            if (type === 'text') {
              toggleIcon.classList.remove('fa-eye-slash');
              toggleIcon.classList.add('fa-eye');
            } else {
              toggleIcon.classList.remove('fa-eye');
              toggleIcon.classList.add('fa-eye-slash');
            }
          });
        }
      }

      // Xử lý submit login
      async function handleLogin(event) {
        event.preventDefault();
        clearError();

        let email = emailInput.value.trim();
        const password = passwordInput.value;

        // Validation
        if (!email) {
          showError('Vui lòng nhập địa chỉ email');
          emailInput.focus();
          return;
        }
        if (!isValidEmail(email)) {
          showError('Email không hợp lệ (ví dụ: name@domain.com)');
          emailInput.focus();
          return;
        }
        if (!password) {
          showError('Mật khẩu không được để trống');
          passwordInput.focus();
          return;
        }

        // Xác thực
        const authResult = authenticateUser(email, password);
        if (!authResult.success) {
          showError(authResult.message);
          passwordInput.value = ''; // xóa mật khẩu để bảo mật
          passwordInput.focus();
          return;
        }

        // Thành công: lưu session & remember me
        const rememberMe = rememberCheckbox.checked;
        saveSession(email, rememberMe);

        // Chuyển sang giao diện Dashboard mock (thay thế nội dung login)
        const mainContainer = document.querySelector('.w-full.max-w-md');
        if (mainContainer) {
          // Lấy tên từ email để chào
          const userName = email.split('@')[0];
          // Tạo hiệu ứng mờ dần rồi thay nội dung bằng dashboard giả lập
          mainContainer.style.transition = 'opacity 0.2s ease';
          mainContainer.style.opacity = '0';
          setTimeout(() => {
            mainContainer.innerHTML = `
              <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-card overflow-hidden transition-all duration-300">
                <div class="p-6 sm:p-8 md:p-9 text-center">
                  <div class="inline-flex items-center justify-center bg-gradient-to-br from-emerald-700 to-teal-800 w-16 h-16 rounded-2xl shadow-md mb-5 mx-auto">
                    <i class="fas fa-chart-pie text-white text-2xl"></i>
                  </div>
                  <h2 class="text-2xl font-bold text-slate-800">Chào mừng, ${userName}!</h2>
                  <p class="text-slate-500 mt-2 mb-6">Bạn đã đăng nhập thành công vào FlowSaaS Dashboard.</p>
                  
                  <!-- Dashboard Widget Preview -->
                  <div class="bg-slate-50 rounded-2xl p-5 text-left border border-slate-100 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                      <i class="fas fa-chart-line text-indigo-600"></i>
                      <span class="font-semibold text-slate-700">Tổng quan analytics</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                      <div class="bg-white p-2.5 rounded-xl shadow-sm">
                        <span class="text-slate-400 block text-xs">Doanh thu</span>
                        <span class="font-bold text-slate-800">+24%</span>
                      </div>
                      <div class="bg-white p-2.5 rounded-xl shadow-sm">
                        <span class="text-slate-400 block text-xs">Người dùng</span>
                        <span class="font-bold text-slate-800">1,482</span>
                      </div>
                      <div class="bg-white p-2.5 rounded-xl shadow-sm">
                        <span class="text-slate-400 block text-xs">Chuyển đổi</span>
                        <span class="font-bold text-slate-800">3.2%</span>
                      </div>
                      <div class="bg-white p-2.5 rounded-xl shadow-sm">
                        <span class="text-slate-400 block text-xs">Phiên</span>
                        <span class="font-bold text-slate-800">+12%</span>
                      </div>
                    </div>
                  </div>
                  
                  <button id="logoutFromDashboard" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-semibold py-2.5 px-6 rounded-xl transition-all duration-200 flex items-center gap-2 mx-auto shadow-sm">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                  </button>
                  <p class="text-xs text-slate-400 mt-5"><i class="fas fa-laptop-code"></i> Giao diện dashboard demo</p>
                </div>
              </div>
            `;
            mainContainer.style.opacity = '1';
            // Gắn sự kiện logout để reset về login
            const logoutBtn = document.getElementById('logoutFromDashboard');
            if (logoutBtn) {
              logoutBtn.addEventListener('click', () => {
                sessionStorage.removeItem('saas_logged_in');
                sessionStorage.removeItem('saas_auth_email');
                window.location.reload(); // Reload về trạng thái login ban đầu
              });
            }
          }, 180);
        } else {
          // fallback
          alert(`✅ Đăng nhập thành công! Chào mừng ${email}`);
          window.location.reload();
        }
      }

      // Khởi tạo các sự kiện và ghi nhớ email
      loadRememberedEmail();
      setupPasswordToggle();
      loginForm.addEventListener('submit', handleLogin);

      // Xóa lỗi khi người dùng bắt đầu nhập lại
      emailInput.addEventListener('input', clearError);
      passwordInput.addEventListener('input', clearError);
    })();
  </script>
</body>

</html>