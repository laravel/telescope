import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

/** @type {import('vitest/config').UserConfig} */
export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.js'],
    },
});
