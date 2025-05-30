import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '127.0.0.1', // Ensures Vite binds to IPv4 localhost
        port: 5173,        // Default Vite port (optional unless changed)
    },
    plugins: [
        laravel({
            input: ['resources/js/app.js'], // Use array to support multiple inputs if needed
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
