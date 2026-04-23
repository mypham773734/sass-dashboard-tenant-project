import './bootstrap';

import Alpine from 'alpinejs';

// Auto-import all pages
Object.values(import.meta.glob('./pages/**/*.js', { eager: true }));

window.Alpine = Alpine;

// Initialize Alpine
Alpine.start();
