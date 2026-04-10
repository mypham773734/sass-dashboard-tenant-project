// Mobile menu toggle (for responsive)
const mobileBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.querySelector('aside');
const dropdownBtn = document.getElementById('dropdownMenuBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

if (dropdownBtn && dropdownMenu) {
    dropdownBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        dropdownMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        if (!dropdownMenu.classList.contains('hidden')) {
            dropdownMenu.classList.add('hidden');
        }
    });

    dropdownMenu.addEventListener('click', (event) => {
        event.stopPropagation();
    });
}

if (mobileBtn) {
    mobileBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
    // close when clicking outside on mobile (optional)
    document.addEventListener('click', function (event) {
        const isClickInside = sidebar.contains(event.target) || mobileBtn.contains(event.target);
        if (!isClickInside && window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.add('-translate-x-full');
        }
    });
}
// Ensure on window resize if screen becomes large, reset sidebar position
window.addEventListener('resize', function () {
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