import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync } from 'node:fs';

// Auto-discover all JS files in resources/js/pages/.
// Adding a new page file here requires no changes to this config.
const pageEntries = readdirSync('./resources/js/pages')
    .filter(f => f.endsWith('.js'))
    .map(f => `resources/js/pages/${f}`);

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js',
                'resources/js/bases/index.js',
                ...pageEntries,
            ],
            refresh: true,
        }),
    ],
    server: { cors: 'http://127.0.0.2:8002' },
});
