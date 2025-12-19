import phpspa from '../../src/script/phpspa.mjs';
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import plaintext from 'highlight.js/lib/languages/plaintext';
import { registerDebugHooks } from './helpers';
import './style.css';
import 'highlight.js/styles/github-dark.css';

hljs.registerLanguage('php', php);
hljs.registerLanguage('plaintext', plaintext);

requestAnimationFrame(() => {
	document
		.querySelectorAll('pre code')
		.forEach(block => hljs.highlightElement(block as HTMLElement));
});

registerDebugHooks(phpspa);
