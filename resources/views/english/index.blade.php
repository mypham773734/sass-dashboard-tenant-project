<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<title>Thử Thách Dịch Thuật | Tiếng Việt → English</title>
	<!-- Tailwind CSS v3 + Font Awesome Icons + Google Fonts -->
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link
		href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap"
		rel="stylesheet">
	<style>
		* {
			font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
		}

		body {
			background: linear-gradient(135deg, #f5f7fc 0%, #eef2f8 100%);
		}

		.card-glass {
			backdrop-filter: blur(2px);
			background-color: rgba(255, 255, 255, 0.96);
			transition: all 0.2s ease;
		}

		textarea:focus {
			box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
		}

		.char-count-badge {
			transition: all 0.1s ease;
		}

		.btn-submit {
			transition: all 0.2s cubic-bezier(0.23, 1, 0.32, 1);
		}

		.btn-submit:active {
			transform: scale(0.96);
		}

		.hover-lift {
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}

		.hover-lift:hover {
			transform: translateY(-2px);
			box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.15);
		}
	</style>
</head>

<body class="antialiased p-4 md:p-6 flex items-center justify-center min-h-screen">

	<div class="md:max-w-7xl w-full mx-auto my-6 md:my-10">
		<!-- main challenge card -->
		<div class="card-glass shadow-2xl shadow-indigo-100/30 overflow-hidden" style="border: 4px solid #3d3d5c">

			<div class="p-6 md:p-8 lg:p-10" style="background-color: #0f0f23">

				<!-- THỨ THÁCH section -->
				<div class="mb-8">
					<div class="flex items-center gap-3 mb-1">
						<i class="fas fa-fire text-orange-500 text-2xl"></i>
						<h1
							class="text-3xl md:text-4xl font-extrabold tracking-tight bg-gradient-to-r  bg-clip-text text-white">
							THỨ THÁCH</h1>
						<span
							class="ml-auto text-xs font-medium px-3 py-1 bg-amber-100 text-amber-800 rounded-full shadow-inner"><i
								class="fas fa-language mr-1"></i> Dịch thuật</span>
					</div>
					<div class="flex justify-between gap-10 mt-10">
						<!-- TIẾNG VIỆT block -->
						<div
							class="bg-gradient-to-br via-white p-5 md:p-6 border shadow-sm" style="border: 4px solid #3d3d5c; background-color: #252542;">
							<div class="flex items-center gap-2 mb-3">
								<i class="fas fa-flag-vn text-red-600 text-lg"></i>
								<span
									class="font-semibold text-indigo-800 tracking-wide text-sm uppercase bg-indigo-100/70 px-3 py-1 rounded-full">TIẾNG
									VIỆT</span>
							</div>
							<div class="py-10 block px-4" style="background-color: #1a1a2e">
								<p
									class="text-base text-white leading-relaxed italic ">
									{{ isset($message) ? $message :  "Tôi thường thức dậy lúc sáu giờ sáng." }}
							</p>
							</div>
	
							<button id="generateBtn"
									class="mt-4 btn-submit bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-bold py-3 px-8 rounded-full shadow-lg flex items-center gap-2 transition-all duration-200">
									<i class="fas fa-paper-plane text-sm"></i>
									Generate Text
								</button>
							<!-- MÔ NGHIỆM - Tiêu chí đánh giá (exact wording from image) -->
							<div class="mb-9 mt-5">
								<div class="flex items-center gap-2 mb-4">
									<i class="fas fa-clipboard-list text-emerald-600 text-xl"></i>
									<h2 class="text-xl font-bold text-white uppercase tracking-wide">MÔ NGHIỆM</h2>
									<div class="h-px flex-1 bg-gray-200 ml-2"></div>
								</div>
								<div class="grid gap-2 md:grid-cols-2">
									<!-- Diễn đạt tự nhiên -->
									<div
										class="bg-white backdrop-blur-sm border border-gray-100 rounded-xl p-4 flex items-start gap-3 shadow-sm hover:shadow-md transition">
										<div class="bg-green-100 p-2 rounded-full"><i
												class="fas fa-leaf text-green-600 text-lg"></i></div>
										<div>
											<h3 class="font-semibold text-gray-800">Diễn đạt tự nhiên</h3>
											<p class="text-xs text-gray-500">Cách diễn đạt mượt mà, tự nhiên như người bản
												xứ</p>
										</div>
									</div>
									<!-- Kiến tra ngữ pháp và thi (original wording) -->
									<div
										class="bg-white backdrop-blur-sm border border-gray-100 rounded-xl p-4 flex items-start gap-3 shadow-sm hover:shadow-md transition">
										<div class="bg-amber-100 p-2 rounded-full"><i
												class="fas fa-spell-check text-amber-600 text-lg"></i></div>
										<div>
											<h3 class="font-semibold text-gray-800">Kiến tra ngữ pháp và thi</h3>
											<p class="text-xs text-gray-500">Cấu trúc đúng, thì chính xác, hạn chế lỗi</p>
										</div>
									</div>
									<!-- Kiến tra từ vựng và ngữ cảnh -->
									<div
										class="bg-white backdrop-blur-sm border border-gray-100 rounded-xl p-4 flex items-start gap-3 shadow-sm hover:shadow-md transition">
										<div class="bg-purple-100 p-2 rounded-full"><i
												class="fas fa-book-open text-purple-600 text-lg"></i></div>
										<div>
											<h3 class="font-semibold text-gray-800">Kiến tra từ vựng và ngữ cảnh</h3>
											<p class="text-xs text-gray-500">Từ vựng phong phú, phù hợp ngữ cảnh</p>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- BẢN DỊCH CỦA BẠN - translation area with char counter -->
						<div class="" style="background-color: #252542; padding: 20px; border: 4px solid #3d3d5c;">
							<div class="flex items-center gap-2 mb-4">
								<i class="fas fa-pen-fancy text-blue-600 text-xl"></i>
								<h2 class="text-xl font-bold text-gray-800 uppercase tracking-wide">BẢN DỊCH CỦA BẠN</h2>
								<div class="h-px flex-1 bg-gray-200 ml-2"></div>
							</div>
	
							<!-- textarea input -->
							<div class="relative">
								<textarea id="translationInput" rows="5" placeholder="Nhập bản dịch tiếng Anh của bạn..." style="background-color: #1a1a2e;     border: 4px solid #3d3d5c;"
									class="w-full px-5 py-4 text-white focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:outline-none transition bg-gray-50/50 resize-y text-base placeholder:text-gray-400"></textarea>
								<div
									class="absolute bottom-3 right-4 text-xs text-gray-400 flex items-center gap-1 bg-white px-2 py-1 rounded-full backdrop-blur-sm">
									<i class="far fa-keyboard"></i>
									<span id="charCountSpan">0</span> <span>chars</span>
								</div>
							</div>
	
							<!-- bottom bar: char count display & submit button -->
							<div class="flex flex-wrap justify-between items-center mt-5 gap-4">
								<div class="flex items-center gap-3 bg-gray-100 rounded-full px-4 py-2 text-sm font-mono">
									<i class="fas fa-text-height text-indigo-500"></i>
									<span class="font-bold text-gray-700" id="charCountDisplay">0 CHARS</span>
									<span class="text-gray-400 text-xs">(không giới hạn)</span>
								</div>
								<button id="submitBtn"
									class="btn-submit bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-bold py-3 px-8 rounded-full shadow-lg flex items-center gap-2 transition-all duration-200">
									<i class="fas fa-paper-plane text-sm"></i>
									Submit
								</button>
							</div>
	
							<!-- submission feedback area (dynamic message) -->
							<div id="feedbackMessage" class="mt-5 text-sm transition-all duration-300 min-h-[3rem]"></div>
	
							<!-- subtle hint / context helper -->
							<div class="mt-4 pt-2 text-xs text-gray-400 border-t border-gray-100 flex gap-3 items-center">
								<i class="fas fa-lightbulb text-amber-400"></i>
								<span>Gợi ý: "I usually wake up at six in the morning." — Dịch tự nhiên, giữ nguyên nghĩa và
									sắc
									thái.</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- decorative footnote -->
		<div class="text-center text-gray-400 text-xs mt-6 flex justify-center gap-4">
			<span><i class="far fa-check-circle text-emerald-400"></i> Tiêu chí chấm điểm</span>
			<span><i class="fas fa-exchange-alt text-sky-400"></i> Dịch thuật chính xác</span>
			<span><i class="far fa-clock"></i> Luyện tập mỗi ngày</span>
		</div>
	</div>

	@vite('resources/js/english/index.js');
	<!-- @vite('resources/js/english/index_backup.js'); -->
</body>

</html>