import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import svgr from 'vite-plugin-svgr';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    react(),
    svgr(),
    tailwindcss(),
    
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
      '@ui': path.resolve(__dirname, 'resources/js/UI'),
      '@admin': path.resolve(__dirname, 'resources/js/Admin'),
      '@Functions': path.resolve(__dirname, 'resources/js/Functions'),
    },
  },
  base: process.env.NODE_ENV === 'production' ? '/build/' : '/',
  build: {
    outDir: 'public/build',
    manifest: true,
    cssCodeSplit: true,  // enable CSS splitting (default)
    rollupOptions: {
      input: {
        homepage: path.resolve(__dirname, 'resources/js/homePage/app.jsx'),
        Auth: path.resolve(__dirname, 'resources/js/Auth/app.jsx'),
        AdminDashboard: path.resolve(__dirname, 'resources/js/Admin/Dashboard/app.jsx'),
      },
    },
  },
  server: {
    strictPort: true,
    port: 5173,
    origin: 'http://localhost:5173',
  },
});
