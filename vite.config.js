import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { quasar, transformAssetUrls } from '@quasar/vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: { transformAssetUrls }
        }),
        quasar({
            sassVariables: false
        }),
        tailwindcss(),
    ],
    // Ensure assets use fixed names in production
    build: {
        manifest: true,
        assetsDir: '',
        rollupOptions: {
            output: {
                manualChunks: undefined,
                entryFileNames: 'assets/app.js',
                chunkFileNames: 'assets/app.js',
                assetFileNames: 'assets/app.[ext]'
            }
        }
    },
});
