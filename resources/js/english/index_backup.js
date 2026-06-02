// DOM elements
const textarea = document.getElementById('translationInput');
const charCountSpan = document.getElementById('charCountSpan');
const charCountDisplay = document.getElementById('charCountDisplay');
const submitBtn = document.getElementById('submitBtn');
const feedbackDiv = document.getElementById('feedbackMessage');

// Function to update character counter (including spaces, line breaks)
function updateCharCounter() {
    const rawText = textarea.value;
    const charLength = rawText.length;
    // update both the small badge and the main char display
    charCountSpan.textContent = charLength;
    charCountDisplay.textContent = `${charLength} CHARS`;

    // Optional: subtle visual feedback when reaching high length (just for style)
    if (charLength > 300) {
        charCountDisplay.classList.add('text-amber-600', 'font-bold');
        charCountDisplay.classList.remove('text-gray-700');
    } else {
        charCountDisplay.classList.remove('text-amber-600', 'font-bold');
        charCountDisplay.classList.add('text-gray-700');
    }
}

// Initialize counter
updateCharCounter();

// Listen to input events
textarea.addEventListener('input', function (e) {
    updateCharCounter();
    // clear any old feedback when user starts typing again (good UX)
    if (feedbackDiv.innerHTML.trim() !== '') {
        // if there's a feedback message older than "success/error", but clear only if not permanent?
        // We'll just clear transient messages, don't remove success permanently?
        // But we remove only if it's a 'dismissable' hint? Actually better reset only error or old info.
        // If message is success or error, we let it disappear after new input? We'll make a smoother UX: 
        // remove feedback when user types again (they modify translation)
        if (feedbackDiv.querySelector('.alert-permanent')) {
            // do not auto-clear if it's a static warning about empty? but we'll handle with dynamic flag
        }
        feedbackDiv.innerHTML = '';  // clear any previous submit feedback to keep UI clean
    }
});

// Helper to show temporary feedback message (success or error)
function showFeedback(message, isError = false) {
    feedbackDiv.innerHTML = '';
    const alertDiv = document.createElement('div');
    alertDiv.className = `rounded-xl p-3 flex items-start gap-3 shadow-sm transition-all fade-in ${isError ? 'bg-red-50 text-red-700 border-l-4 border-red-500' : 'bg-emerald-50 text-emerald-800 border-l-4 border-emerald-500'
        }`;
    const icon = isError ? '<i class="fas fa-exclamation-triangle text-red-500 mr-1"></i>' : '<i class="fas fa-check-circle text-emerald-500 mr-1"></i>';
    alertDiv.innerHTML = `
        <div class="flex-1 text-sm font-medium flex items-center gap-2">
          ${icon}
          <span>${message}</span>
        </div>
        <button class="text-gray-400 hover:text-gray-600 transition ml-2" onclick="this.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      `;
    feedbackDiv.appendChild(alertDiv);

    // auto remove after 4 seconds for non-critical messages (except if error stays a bit longer)
    setTimeout(() => {
        if (feedbackDiv.contains(alertDiv)) {
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                if (alertDiv.parentNode) alertDiv.remove();
            }, 200);
        }
    }, 4500);
}

// --- Evaluate translation mock / interactive submission (challenge core)
// This is purely front-end interaction: shows confirmation and "matching criteria" assessment.
// However we definitely imitate the challenge submission with natural feedback.
submitBtn.addEventListener('click', function () {
    const translation = textarea.value.trim();
    const charLen = translation.length;

    // Validation: empty translation
    if (translation === "") {
        showFeedback("⚠️ Bạn chưa nhập bản dịch. Hãy điền bản dịch tiếng Anh của câu trên trước khi nộp bài.", true);
        // subtle shake animation on textarea (optional)
        textarea.classList.add('border-red-400', 'ring-red-100');
        setTimeout(() => textarea.classList.remove('border-red-400', 'ring-red-100'), 600);
        return;
    }

    // get the original Vietnamese sentence for reference
    const originalVietnamese = "Tôi thường thức dậy lúc sáu giờ sáng.";

    // Basic evaluation hints: we provide realistic feedback based on translation quality (but not strict backend - it's UI demo)
    // We'll analyze the translation lowercase to give helpful & fun suggestions based on keywords
    const lowerTransl = translation.toLowerCase();

    // simple heuristic to mimic "MÔ NGHIỆM" criteria 
    let naturalScore = 0;     // natural expression
    let grammarScore = 0;     // grammar & tense
    let vocabScore = 0;       // vocabulary and context

    // KEYWORD BASED FEEDBACK (non-exhaustive but for interactive demonstration)
    // common expected translation: "I usually wake up at six in the morning" / "I usually get up at 6 am"
    const hasUsually = /usually|typically|normally/.test(lowerTransl);
    const hasWake = /wake up|get up|rise/.test(lowerTransl);
    const hasSix = /six|6|06/.test(lowerTransl);
    const hasMorning = /morning|a\.m\.|am|a.m/.test(lowerTransl);
    const hasAt = /at/.test(lowerTransl);
    const hasI = /^i\b|\si\s/.test(lowerTransl);

    // Natural expression: natural flow and common phrasing
    if (hasUsually && hasWake && hasAt && hasMorning) naturalScore = 2;
    else if ((hasWake || hasUsually) && hasMorning) naturalScore = 1;
    else naturalScore = 0;

    // Grammar & tense: present simple, subject-verb agreement, correct prepositions
    let grammarIndicators = 0;
    if (hasI) grammarIndicators++;
    if (hasUsually && /wake|get/.test(lowerTransl)) grammarIndicators++;
    if (/\b(at|in the)\b/.test(lowerTransl) && hasMorning) grammarIndicators++;
    if (lowerTransl.match(/six|6/) && lowerTransl.match(/morning|a\.m\./)) grammarIndicators++;
    grammarScore = grammarIndicators >= 3 ? 2 : (grammarIndicators >= 1 ? 1 : 0);

    // Vocabulary & context: variety, appropriate word choice
    if (hasUsually && (hasWake || hasGetUp) && (hasSix && hasMorning)) vocabScore = 2;
    else if ((hasWake || hasGetUp) && hasSix) vocabScore = 1;
    else vocabScore = 0;

    // Build detail feedback based on MÔ NGHIỆM
    let overallMessage = "";
    let isExcellent = (naturalScore + grammarScore + vocabScore) >= 5;

    if (isExcellent) {
        overallMessage = "🎉 Xuất sắc! Bản dịch của bạn đáp ứng tốt cả ba tiêu chí: diễn đạt tự nhiên, ngữ pháp chính xác, từ vựng phù hợp ngữ cảnh. 📝✨";
    } else {
        // constructive feedback
        let missing = [];
        if (naturalScore <= 0) missing.push("• Diễn đạt chưa thật tự nhiên (hãy thử dùng 'usually' hoặc cấu trúc quen thuộc)");
        if (grammarScore <= 0) missing.push("• Kiểm tra ngữ pháp và thì: cần đúng thì hiện tại đơn, chủ ngữ + động từ");
        if (vocabScore <= 0) missing.push("• Từ vựng & ngữ cảnh: thêm từ miêu tả thời gian rõ ràng (six in the morning)");
        if (missing.length === 0 && (naturalScore === 1 || grammarScore === 1 || vocabScore === 1)) {
            overallMessage = "👍 Bản dịch khá tốt! Bạn có thể cải thiện thêm về sự tự nhiên hoặc dùng từ đa dạng hơn để đạt điểm tối đa.";
        } else if (missing.length) {
            overallMessage = `🔍 Gợi ý cải thiện theo MÔ NGHIỆM:<br/> ${missing.join('<br/>')}`;
        } else {
            overallMessage = "📖 Bản dịch đã nộp. Hãy đối chiếu với gợi ý: 'I usually wake up at six in the morning.' để trau dồi thêm.";
        }
    }

    // Generate special contextual snippet: Include what user wrote
    const userTranslationPreview = translation.length > 120 ? translation.slice(0, 117) + '...' : translation;

    // Final composition: show submission acknowledgment + analysis
    const feedbackHtml = `
        <div class="bg-white rounded-xl p-4 shadow-md border border-gray-100 space-y-3">
          <div class="flex items-start gap-3">
            <div class="bg-indigo-100 rounded-full p-2"><i class="fas fa-file-alt text-indigo-600"></i></div>
            <div class="flex-1">
              <p class="font-bold text-gray-800">✅ Đã nộp bài thành công!</p>
              <p class="text-sm text-gray-600 mt-1"><span class="font-medium">Bản dịch của bạn:</span> “${userTranslationPreview}”</p>
              <div class="text-xs text-gray-400 mt-1">Số ký tự: ${charLen} ký tự</div>
            </div>
          </div>
          <div class="bg-gray-50 p-3 rounded-lg border-l-4 ${isExcellent ? 'border-emerald-400' : 'border-amber-300'}">
            <div class="flex items-center gap-2 text-sm font-medium ${isExcellent ? 'text-emerald-700' : 'text-gray-700'}">
              <i class="fas fa-chart-line"></i> 
              <span>Đánh giá nhanh theo MÔ NGHIỆM</span>
            </div>
            <div class="text-sm mt-1 text-gray-700 leading-relaxed">${overallMessage}</div>
            ${!isExcellent ? `<div class="mt-2 text-xs text-indigo-500"><i class="fas fa-lightbulb"></i> Tham khảo gợi ý dịch: "I usually wake up at six in the morning."</div>` : ''}
          </div>
          <div class="flex justify-end gap-2 text-xs text-gray-400 pt-1">
            <i class="far fa-smile-wink"></i> <span>Tiếp tục luyện tập để cải thiện từ vựng & ngữ pháp!</span>
          </div>
        </div>
      `;

    // Clear previous feedback and show detailed analysis
    feedbackDiv.innerHTML = '';
    const resultContainer = document.createElement('div');
    resultContainer.innerHTML = feedbackHtml;
    feedbackDiv.appendChild(resultContainer);

    // Also scroll into view smoothly to see the detailed feedback
    feedbackDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // additional highlight effect on submit button
    submitBtn.classList.add('scale-105');
    setTimeout(() => submitBtn.classList.remove('scale-105'), 150);
});

// Add extra input focus management and "Enter" does not submit accidentally (ignore)
textarea.addEventListener('keydown', (e) => {
    // Ctrl+Enter or Cmd+Enter also could submit for convenience (nice extra)
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        submitBtn.click();
    }
});

// Dynamic placeholder hint when empty (optional style)
const adjustPlaceholder = () => {
    if (textarea.value.length === 0) {
        textarea.setAttribute('placeholder', 'Nhập bản dịch tiếng Anh của bạn... Ví dụ: "I usually wake up at six in the morning."');
    } else {
        textarea.setAttribute('placeholder', 'Nhập bản dịch tiếng Anh của bạn...');
    }
};
textarea.addEventListener('focus', adjustPlaceholder);
textarea.addEventListener('blur', () => {
    if (textarea.value.length === 0) {
        textarea.setAttribute('placeholder', 'Nhập bản dịch tiếng Anh của bạn...');
    }
});
adjustPlaceholder();