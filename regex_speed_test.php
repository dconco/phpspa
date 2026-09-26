<?php

$pattern = '/<([@\$]?[A-Z][a-zA-Z0-9.:]*)([^>]*)(?:\/>|>([\s\S]*?)<\/\1>)/';

$html = str_repeat(
    '<div class="test"><p>Hello World</p></div>',
    250000
);

$iterations = 100;

// Warm up
preg_match($pattern, $html);
md5($html);

function benchmark(callable $callback, int $iterations): float
{
    $start = hrtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }

    return (hrtime(true) - $start) / 1e6 / $iterations;
}

echo 'HTML size: ' . round(strlen($html) / 1048576, 2) . " MB\n";

echo 'MD5: ' . benchmark(
    fn() => md5($html),
    $iterations
) . " ms\n";

echo 'Regex: ' . benchmark(
    fn() => preg_match($pattern, $html),
    $iterations
) . " ms\n";

echo 'Regex Callback: ' . benchmark(
    fn() => preg_replace_callback(
        $pattern,
        fn($matches) => $matches[0],
        $html
    ),
    $iterations
) . " ms\n";