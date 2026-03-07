import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    proxy: {
      '/api/products': { target: 'http://localhost:8001', changeOrigin: true, rewrite: (p) => p.replace(/^\/api\/products/, '/api/v1') },
      '/api/inventory': { target: 'http://localhost:8002', changeOrigin: true, rewrite: (p) => p.replace(/^\/api\/inventory/, '/api/v1') },
      '/api/orders':    { target: 'http://localhost:8003', changeOrigin: true, rewrite: (p) => p.replace(/^\/api\/orders/, '/api/v1') },
      '/api/users':     { target: 'http://localhost:8004', changeOrigin: true, rewrite: (p) => p.replace(/^\/api\/users/, '/api/v1') },
    },
  },
});
