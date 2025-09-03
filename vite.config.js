import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'src/scripts/admin.ts'),
        'admin-style': resolve(__dirname, 'src/styles/admin.scss')
      },
      output: {
        entryFileNames: (chunkInfo) => {
          return chunkInfo.name.endsWith('-style') ? 'styles/[name].js' : 'scripts/[name].js';
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'styles/[name][extname]';
          }
          return 'assets/[name][extname]';
        }
      }
    },
    // Generate manifest for WordPress integration
    manifest: true
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler'
      }
    }
  },
  server: {
    host: 'localhost',
    port: 3000,
    strictPort: true
  }
});
