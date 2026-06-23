import { fileURLToPath, URL } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./resources/js/test/setup.ts'],
        css: false,
        restoreMocks: true,
        clearMocks: true,
        fileParallelism: true,
        maxWorkers: 4,
        pool: 'threads',
        testTimeout: 10_000,
        hookTimeout: 10_000,
        teardownTimeout: 5_000,
    },
});
