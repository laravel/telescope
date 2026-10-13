import vue2 from '@vitejs/plugin-vue2';
import { defineConfig } from 'vitest/config';

/** @type {import('vitest/config').UserConfig} */
export default defineConfig({
    plugins: [vue2()],
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.js'],
    },
});
