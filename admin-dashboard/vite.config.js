import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/charts.js', 'resources/css/customer.scss', 'resources/js/customer.js'],
            refresh: true,
            fonts: [
                bunny('Open Sans', { weights: [400, 500, 600] }),
                bunny('Playfair Display', { weights: [400, 500, 600] }),
                bunny('Geist', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    css: {
        preprocessorOptions: {
            scss: { quietDeps: true, silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'] },
        },
    },
    server: {
        port: 5176,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
