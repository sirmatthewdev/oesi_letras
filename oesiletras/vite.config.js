import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        cssMinify: true,
        minify: 'esbuild',
        // es2015 rompe el uso interno de funciones async nativas de Alpine.js
        // (AsyncFunction se transpila a algo que ya no tiene .catch) — es2020
        // es el objetivo mínimo real que soportan los navegadores modernos.
        target: 'es2020',
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                manualChunks: undefined,
                assetFileNames: 'assets/[name]-[hash][extname]',
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
        },
    },
});
