import vue2 from '@vitejs/plugin-vue2';

/** @type {import('vite').UserConfig} */
export default {
    plugins: [vue2()],
    build: {
        assetsDir: '',
        rollupOptions: {
            input: ['resources/js/app.js', 'resources/sass/styles.scss', 'resources/sass/styles-dark.scss'],
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm.js',
        },
    },
};
