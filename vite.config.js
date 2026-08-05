import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/code-editor.js', 'resources/js/file-manager.js', 'resources/js/server-metrics.js', 'resources/js/two-factor-qr.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
