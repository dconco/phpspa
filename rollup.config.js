import { readFileSync } from 'node:fs';

import typescript from '@rollup/plugin-typescript';
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import terser from '@rollup/plugin-terser';

const pkg = JSON.parse(
   readFileSync(new URL('./package.json', import.meta.url), 'utf-8')
);

const banner = `/*!\n * PhpSPA Client Runtime v${pkg.version}\n * Docs: ${pkg.homepage} | Package: ${pkg.name}\n * License: ${pkg.license}\n */`;

export default {
   input: 'lib/index.ts',
   output: [
      // --- UMD build for browsers and CDN ---
      {
         file: 'src/script/phpspa.js',
         format: 'umd',
         name: 'phpspa',
         exports: 'named',
         banner
      },
      // --- Minified UMD for production CDN ---
      {
         file: 'src/script/phpspa.min.js',
         format: 'umd',
         name: 'phpspa',
         exports: 'named',
         plugins: [terser()],
         banner
      },
      // --- CommonJS for Node.js ---
      {
         file: 'src/script/phpspa.cjs',
         format: 'cjs',
         exports: 'named',
         banner
      },
      // --- ES Module for modern bundlers ---
      {
         file: 'src/script/phpspa.mjs',
         format: 'es',
         banner
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
