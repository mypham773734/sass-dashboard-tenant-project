import './bootstrap';

// Auto-import all pages
Object.values(import.meta.glob('./pages/**/*.js', { eager: true }));

// Alpine is initialized by Livewire's @livewireScripts — do not start a second instance.
