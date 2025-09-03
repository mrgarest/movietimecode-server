import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/flaticon-uicons-main/src/uicons/css/all/all.css',
                'resources/js/app.js',
                'resources/js/json-page-data.js',
                'resources/js/auth.js',
            ],
            refresh: true,
        }),
    ],
});
