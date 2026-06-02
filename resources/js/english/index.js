(function ($) {
    // DOM elements
    const textarea = $('#translationInput');
    const charCountSpan = $('#charCountSpan')
    const charCountDisplay = $('#charCountDisplay')
    const submitBtn = $('#submitBtn')
    const feedbackDiv = $('#feedbackMessage')

    const EnglishScript = {
        init: () => {
            EnglishScript.updateCharCounter();
            textarea.on('input', EnglishScript.handleChangeEnglishMessage());
            submitBtn.on('click', EnglishScript.handleSubmitEnglishMessage());
        },
        updateCharCounter: function () {
            const rawText = textarea.val();
            const charLength = rawText.val().length;

            // update both the small badge and the main char display
            charCountSpan.text(charLength)
            charCountDisplay.text(`${charLength} CHARS`)

            // Optional: subtle visual feedback when reaching high length (just for style)
            if (charLength > 300) {
                charCountDisplay.addClass('text-amber-600 font-bold').removeClass('text-gray-700');
            } else {
                charCountDisplay.addClass('text-gray-700').removeClass('text-amber-600 font-bold');
            }
        },
        handleChangeEnglishMessage: function () {
            EnglishScript.updateCharCounter();

            if ($(feedbackDiv).trim() !== '') {
                if ($(feedbackDiv).find('.alert-permanent')) { }
                $(feedbackDiv).html('');
            }
        },
        handleSubmitEnglishMessage: function () {
            const translation = $(textarea).val().trim();
            const charLen = translation.length;

            if (translation === "") {
                showFeedback("⚠️ Bạn chưa nhập bản dịch. Hãy điền bản dịch tiếng Anh của câu trên trước khi nộp bài.", true);
                // subtle shake animation on textarea (optional)
                textarea.classList.add('border-red-400', 'ring-red-100');
                textarea.addClass('border-red-400 ring-red-100');
                setTimeout(() => textarea.removeClass('border-red-400 ring-red-100'), 600);
                return;
            }
        },
        showFeedback: function (message, isError = false) {
            feedbackDiv.html('');
            const alertDiv = $('<div></div>')
            alertDiv.addClass(`rounded-xl p-3 flex items-start gap-3 shadow-sm transition-all fade-in ${isError ? 'bg-red-50 text-red-700 border-l-4 border-red-500' : 'bg-emerald-50 text-emerald-800 border-l-4 border-emerald-500'}`);
            const icon = isError ? '<i class="fas fa-exclamation-triangle text-red-500 mr-1"></i>' : '<i class="fas fa-check-circle text-emerald-500 mr-1"></i>';
            alertDiv.html(`
                <div class="flex-1 text-sm font-medium flex items-center gap-2">
                ${icon}
                <span>${message}</span>
                </div>
                <button class="text-gray-400 hover:text-gray-600 transition ml-2" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
                </button>
            `);
            feedbackDiv.append(alertDiv);

            // auto remove after 4 seconds for non-critical messages (except if error stays a bit longer)
            setTimeout(() => {
                if (feedbackDiv.has(alertDiv).length) {
                    alertDiv.css('opacity', '0');
                    setTimeout(() => {
                        if (alertDiv.parent().length) {
                            alertDiv.remove();
                        }
                    }, 200);
                }
            }, 4500);
        }
    }

    $(document).ready(() => {
        EnglishScript.init();
    });
})(jQuery); 