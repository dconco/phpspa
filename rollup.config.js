import typescript from '@rollup/plugin-typescript';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import terser from '@rollup/plugin-terser';

export default {
   input: 'lib/index.ts',
   output: [
      // --- UMD build for browsers and CDN ---
      {
         file: 'src/script/phpspa.js',
         format: 'umd',
         name: 'phpspa',
         sourcemap: true,
         exports: 'named'
      },
      // --- Minified UMD for production CDN ---
      {
         file: 'src/script/phpspa.min.js',
         format: 'umd',
         name: 'phpspa',
         sourcemap: true,
         exports: 'named',
         plugins: [terser()]
      },
      // --- CommonJS for Node.js ---
      {
         file: 'src/script/phpspa.cjs',
         format: 'cjs',
         sourcemap: true,
         exports: 'named'
      },
      // --- ES Module for modern bundlers ---
      {
         file: 'src/script/phpspa.mjs',
         format: 'es',
         sourcemap: true
      }
   ],
   plugins: [
      resolve({
         browser: true
      }),
      commonjs(),
      typescript({
         tsconfig: './tsconfig.json',
         declaration: true,
         declarationDir: './src/script/types',
         rootDir: './lib'
      })
   ]
};
