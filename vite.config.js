import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/map.js', 'resources/js/share.js', 'resources/js/gallery.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        // map.js es su propio entry (mapbox-gl, ~1.9 MB) y solo se carga en las
        // vistas con mapa; el default de 500 KB de Rollup solo genera ruido en
        // el log de build para un caso ya esperado.
        chunkSizeWarningLimit: 2000,
    },
});
