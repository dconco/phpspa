<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/vendor/autoload.php';

putenv('PHPSPA_COMPRESSION_STRATEGY=fallback');
\PhpSPA\Compression\Compressor::setLevel(\PhpSPA\Compression\Compressor::LEVEL_BASIC);

$snippet = <<<HTML
<div>
   <pre>line 1
   line 2

&lt;div class="x"&gt;hi&lt;/div&gt;
</pre>
   <textarea>alpha
   beta</textarea>
   <code>const x = 1;
const y = 2;</code>
</div>
HTML;

$out = \PhpSPA\Compression\Compressor::compress($snippet);

echo "OUT:\n" . $out . "\n===\n";

preg_match('~<pre[^>]*>(.*?)</pre>~s', $out, $mPre);
preg_match('~<textarea[^>]*>(.*?)</textarea>~s', $out, $mTa);
preg_match('~<code[^>]*>(.*?)</code>~s', $out, $mCode);

echo "PRE_MATCH=" . (isset($mPre[1]) ? 'yes' : 'no') . "\n";
echo "TEXTAREA_MATCH=" . (isset($mTa[1]) ? 'yes' : 'no') . "\n";
echo "CODE_MATCH=" . (isset($mCode[1]) ? 'yes' : 'no') . "\n";

echo "PRE=\n" . ($mPre[1] ?? 'NO_MATCH') . "\n---\n";
echo "TEXTAREA=\n" . ($mTa[1] ?? 'NO_MATCH') . "\n---\n";
echo "CODE=\n" . ($mCode[1] ?? 'NO_MATCH') . "\n---\n";
