import phpspa from '@dconco/phpspa';
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import plaintext from 'highlight.js/lib/languages/plaintext';
import { registerDebugHooks } from './helpers';
import 'highlight.js/styles/github-dark.css';
import './style.css';

phpspa.on('load', () => {
   hljs.registerLanguage('php', php);
   hljs.registerLanguage('plaintext', plaintext);

   requestAnimationFrame(() => {
      document.querySelectorAll('pre').forEach(pre => {
         const block = (pre.querySelector('code') ?? pre) as HTMLElement;
         block.style.background = 'transparent';
         hljs.highlightElement(block);
      });
   });
})

registerDebugHooks(phpspa);
