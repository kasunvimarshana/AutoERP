import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

const NODE_MODULES_PATH_SEGMENT = '/node_modules/';
const VENDOR_CHUNK_NAME = 'vendor';

function splitVendorChunk(moduleId: string): string | undefined {
    const normalizedModuleId = moduleId.replaceAll('\\', '/');
    return normalizedModuleId.includes(NODE_MODULES_PATH_SEGMENT) ? VENDOR_CHUNK_NAME : undefined;
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: splitVendorChunk,
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },

});
