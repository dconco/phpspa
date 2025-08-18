<?php

require_once 'vendor/autoload.php';
use phpSPA\Compression\Compressor;
use phpSPA\Core\Utils\HtmlCompressor;

function compressJs(string $js): string
{
    HtmlCompressor::setLevel(Compressor::LEVEL_EXTREME);
    $input = "<script>" . $js . "</script>";
    return HtmlCompressor::compress($input);
}

$tests = [
    [
        'name' => 'paren close then identifier: )btn -> );btn',
        'js' => "const btn=document.getElementById('btn')\nbtn.onclick=async()=>{console.log('x')}",
        'mustContain' => [");btn"],
        'mustNotContain' => [")btn"],
    ],
    [
        'name' => 'Tagged template then identifier (should insert semicolon before const)',
        'js' => "tag`tmpl`\nconst a=1",
        'mustContain' => [";const"],
        'mustNotContain' => ["` const"],
    ],
    [
        'name' => 'Plain template then identifier (should insert semicolon before let)',
        'js' => "`x\${1+2}`\nlet b=2",
        'mustContain' => [";let"],
        'mustNotContain' => ["` let"],
    ],
    [
        'name' => 'Nullish coalescing expression then const',
        'js' => "(a ?? b)\nconst c=3",
        'mustContain' => [");const"],
        'mustNotContain' => [")const"],
    ],
    [
        'name' => 'await then export default',
        'js' => "await something()\nexport default foo",
        'mustContain' => [");export default"],
        'mustNotContain' => [")export default"],
    ],
    [
        'name' => 'Top-level await then class',
        'js' => "await Promise.resolve()\nclass C{}",
        'mustContain' => [");class"],
        'mustNotContain' => [")class"],
    ],
    [
        'name' => 'Return then IIFE should insert semicolon',
        'js' => "function f(){return\n(function(){})()}",
        'mustContain' => ["return;(function"],
        'mustNotContain' => ["return (function"],
    ],
    [
        'name' => 'Expression then new on next line',
        'js' => "doSomething()\nnew Date()",
        'mustContain' => [");new"],
        'mustNotContain' => [")new"],
    ],
    [
        'name' => 'Generator yield on next line after expression',
        'js' => "function* g(){x()\nyield 1}",
        'mustContain' => [");yield"],
        'mustNotContain' => [")yield"],
    ],
    [
        'name' => 'export default after block close',
        'js' => "{let a=1}\nexport default a",
        'mustContain' => ["};export default"],
        'mustNotContain' => ["}export default"],
    ],
    [
        'name' => 'else if remains intact (no semicolon between)',
        'js' => "if(x){a()}\nelse if(y){b()}",
        'mustContain' => ["}else if("],
        'mustNotContain' => ["};if("],
    ],
    [
        'name' => 'optional catch binding remains intact',
        'js' => "try{a()}\ncatch{b()}",
        'mustContain' => ["}catch{"],
        'mustNotContain' => ["};catch{"],
    ],
    [
        'name' => 'Expression then IIFE on next line',
        'js' => "let a=1\n(function(){return a})()",
        'mustContain' => ["1;(function"],
        'mustNotContain' => ["1(function"],
    ],
    [
        'name' => 'Expression then async IIFE on next line',
        'js' => "const n=0\n(async function(){await Promise.resolve(n)})()",
        'mustContain' => ["0;(async function"],
        'mustNotContain' => ["0(async function"],
    ],
    [
        'name' => 'IIFE then const on next line requires semicolon',
        'js' => "(function(){console.log('iife')})()\nconst x=1",
        'mustContain' => [");const"],
        'mustNotContain' => [")const"],
    ],
    [
        'name' => 'Call then class declaration',
        'js' => "doSomething()\nclass X{}",
        'mustContain' => [");class"],
        'mustNotContain' => [")class"],
    ],
    [
        'name' => 'Call then function declaration',
        'js' => "fn()\nfunction g(){}",
        'mustContain' => [");function"],
        'mustNotContain' => [")function"],
    ],
    [
        'name' => 'Array close then const',
        'js' => "[1,2,3]\nconst z=3",
        'mustContain' => ["];const"],
        'mustNotContain' => ["]const"],
    ],
    [
        'name' => 'Block close then import/export at top-level',
        'js' => "{let a=1}\nexport const q=1",
        'mustContain' => ["};export"],
        'mustNotContain' => ["}export"],
    ],
    [
        'name' => 'Await expr then const',
        'js' => "await fetch('/api')\nconst data=1",
    'mustContain' => [");const"],
    'mustNotContain' => [") const"],
    ],
    [
        'name' => 'Promise chain then const',
        'js' => "fetch('/a').then(r=>r.json())\nconst k=0",
        'mustContain' => [");const"],
        'mustNotContain' => [")const"],
    ],
    [
        'name' => 'try/catch should NOT get semicolon before catch/finally',
        'js' => "try{a()}\ncatch(e){b()}\nfinally{c()}",
        'mustContain' => ["}catch(", "}finally{"],
        'mustNotContain' => ["};catch(", "};finally{"],
    ],
    [
        'name' => 'Template literal inside call then const',
    'js' => "console.log(`hi \${1+2}`)\nconst z=0",
        'mustContain' => [");const"],
        'mustNotContain' => [")const"],
    ],
    [
        'name' => 'Regex literal via .test() then const',
        'js' => "/ab+c/i.test(str)\nconst x=1",
        'mustContain' => [");const"],
        'mustNotContain' => [")const"],
    ],
    [
        'name' => 'Optional chaining then const',
        'js' => "obj?.method()?.prop\nconst y=2",
        'mustContain' => ["prop;const"],
        'mustNotContain' => ["prop const"],
    ],
    [
        'name' => 'Dynamic import then const',
        'js' => "import('x').then(m=>m.x())\nconst k=5",
        'mustContain' => [");const"],
        'mustNotContain' => [")const"],
    ],
    [
        'name' => 'for-of loop then const',
        'js' => "for(const a of [1,2]){console.log(a)}\nconst n=9",
        'mustContain' => ["};const"],
        'mustNotContain' => ["}const"],
    ],
    [
        'name' => 'for-await-of async loop then const',
        'js' => "async function r(){for await (const a of xs){await a()}}\nconst n=10",
        'mustContain' => ["};const"],
        'mustNotContain' => ["}const"],
    ],
    [
        'name' => 'block close then const: }const -> };const',
        'js' => "let a=()=>{}\nconst b=1",
        'mustContain' => ["};const"],
        'mustNotContain' => ["}const"],
    ],
    [
        'name' => 'bracket close then identifier: ]log -> ];log',
        'js' => "const arr=[1,2,3]\nlog(arr.length)",
        'mustContain' => ["];log"],
        'mustNotContain' => ["]log"],
    ],
    [
        'name' => 'identifier before keyword: x=1 const -> x=1;const',
        'js' => "x=1\nconst y=2",
        'mustContain' => ["1;const"],
        'mustNotContain' => ["1const"],
    ],
    [
        'name' => 'do-while should NOT insert before while',
        'js' => "do{a()}\nwhile(x)",
        'mustContain' => ["}while("],
        'mustNotContain' => ["};while("],
    ],
    [
        'name' => 'else should NOT get a preceding semicolon',
        'js' => "if(x){a()}\nelse{b()}",
        'mustContain' => ["}else{"],
        'mustNotContain' => ["};else{"],
    ],
];

$allPassed = true;
foreach ($tests as $t) {
    $out = compressJs($t['js']);
    $pass = true;
    foreach ($t['mustContain'] as $needle) {
        if (strpos($out, $needle) === false) {
            $pass = false;
        }
    }
    foreach ($t['mustNotContain'] as $needle) {
        if (strpos($out, $needle) !== false) {
            $pass = false;
        }
    }

    echo "\n=== Test: {$t['name']} ===\n";
    echo ($pass ? "PASS" : "FAIL") . "\n";
    if (!$pass) {
        echo "Output:\n$out\n";
    }
    $allPassed = $allPassed && $pass;
}

echo "\nSummary: " . ($allPassed ? "ALL PASSED" : "SOME FAILED") . "\n";
