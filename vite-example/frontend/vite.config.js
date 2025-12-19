import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  build: {
    outDir: '../public/assets',
    emptyOutDir: true,
    manifest: true, // Generate manifest.json
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/main.js')
      },
      output: {
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash].[ext]'
      }
    }
  },
  server: {
    // Proxy API requests to PHP server during development
    proxy: {
      '/api': 'http://localhost:8000'
    }
  }
})
