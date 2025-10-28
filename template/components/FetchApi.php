<?php

use PhpSPA\Component;
use PhpSPA\Core\Client\AsyncResponse;

use function Component\useFetch;
use function Component\useState;

return (new Component(function ()
{

   $data = useState('data', null);

   $html = "<h2>Testing Async HTTP Requests</h2>";
   $html .= "<hr>";

   // Test 1: Single async request
   $html .= "<h3>Test 1: Single Async Request</h3>";
   $start = microtime(true);

   $async = useFetch('https://jsonplaceholder.typicode.com/posts/1')->async()->get();
   $html .= "Request initiated (non-blocking)...<br>";

   $response = $async->wait();
   $duration = round((microtime(true) - $start) * 1000, 2);

   if ($response->ok()) {
      $res = $response->json();
      $html .= "✅ Success! Duration: {$duration}ms<br>";
      $html .= "Title: " . ($res['title'] ?? 'N/A') . "<br>";
   }
   else {
      $html .= "❌ Failed: " . $response->error() . "<br>";
   }

   $html .= "<hr>";

   // Test 2: Parallel requests
   $html .= "<h3>Test 2: Parallel Requests (True Concurrency)</h3>";
   $start = microtime(true);

   $requests = [
      useFetch('https://jsonplaceholder.typicode.com/posts/1')->async()->get(),
      useFetch('https://jsonplaceholder.typicode.com/users/1')->async()->get(),
      useFetch('https://jsonplaceholder.typicode.com/comments/1')->async()->get(),
   ];

   $html .= "3 requests initiated (parallel execution)...<br>";

   $responses = AsyncResponse::all($requests);
   $duration = round((microtime(true) - $start) * 1000, 2);

   $html .= "✅ All completed! Total duration: {$duration}ms<br>";
   $html .= "Results:<br>";

   foreach ($responses as $i => $response) {
      if ($response->ok()) {
         $res = $response->json();
         $html .= "• Request " . ($i + 1) . ": Success - " . json_encode(array_slice($res, 0, 2)) . "<br>";
      }
      else {
         $html .= "• Request " . ($i + 1) . ": Failed - " . $response->error() . "<br>";
      }
   }

   $html .= "<hr>";

   // Test 3: Using then() callback
   $html .= "<h3>Test 3: Using then() Callback</h3>";
   $start = microtime(true);

   useFetch('https://jsonplaceholder.typicode.com/posts/2')
      ->async()
      ->get()
      ->then(function ($response) use ($start, &$html)
      {
         $duration = round((microtime(true) - $start) * 1000, 2);

         if ($response->ok()) {
            $res = $response->json();

            $html .= "✅ Callback executed! Duration: {$duration}ms<br>";
            $html .= "Post ID: " . ($res['id'] ?? 'N/A') . " - Title: " . ($res['title'] ?? 'N/A') . "<br>";
         }
         else {
            $html .= "❌ Failed: " . $response->error() . "<br>";
         }
      });

   $html .= "<hr>";
   $html .= "<p><strong>All tests completed!</strong></p>";

   return $html;
}))->route('/fetch-api');