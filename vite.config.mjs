import vue2 from "@vitejs/plugin-vue2";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import manifestSRI from "vite-plugin-manifest-sri";

const config = defineConfig({
    plugins: [
        laravel([
            "resources/sass/styles.scss",
            "resources/sass/styles-dark.scss",
            "resources/js/app.js",
        ]),
        vue2(),
        manifestSRI(),
    ],
    resolve: {
        alias: {
            vue: "vue/dist/vue.esm.js",
        },
    },
    build: {
        rollupOptions: {
            output: {
                entryFileNames: `[name].js`,
                chunkFileNames: `[name].js`,
                assetFileNames: `[name].[ext]`,
            },
        },
    },
});

export default config;
