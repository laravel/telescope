import {defineConfig} from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue2';

export default defineConfig({
    plugins: [
        laravel([
            'resources/sass/app.scss',
            'resources/sass/app-dark.scss',
            'resources/js/app.js',
        ]),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        symlinks: false,
        alias: {
            '@': '/resources/js',
            vue: 'vue/dist/vue.esm.js',
        },
    },
});
