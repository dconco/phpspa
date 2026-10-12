<?php

function calculate(string $string) {
   $iterations = 10000;

   $start = hrtime(true);

   for ($i = 0; $i < $iterations; $i++) {
      md5($string);
   }

   $elapsed = (hrtime(true) - $start) / 1e6;
   return $elapsed;
}


// --- TEST ON 118KB STRING ---
$elapsed = calculate(str_repeat('A', 118 * 1024));

echo '118KB Average: ' . ($elapsed / 10000) . " ms\n";

// --- TEST ON 10MB STRING ---
$string2 = calculate(str_repeat('A', 10 * 1024 * 1024));

echo '10MB Average: ' . ($string2 / 10000) . " ms\n";
