import { defineConfig } from 'vite';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectDir = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
  build: {
    outDir: './public/assets',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(projectDir, 'src/main.ts')
      },
      output: {
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash].[ext]'
      }
    }
  },
  server: {
    proxy: {
      '/api': 'http://localhost:2000' // --- Your Server URL ---
    }
  }
});
