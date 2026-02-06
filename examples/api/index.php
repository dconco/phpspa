<?php

require_once '../../vendor/autoload.php';

use PhpSPA\App;
use PhpSPA\Compression\Compressor;
use PhpSPA\Http\Request;
use PhpSPA\Http\Response;
use PhpSPA\Http\Router;

$app = new App();

$app->useStatic('/', '../public');

$app->compression(Compressor::LEVEL_AUTO);

// $app->prefix('/', function (Router $router) {
//    $router->get('/', function(Request $req, Response $res) {
//       return $res->sendFile('../public/index.html');
//    });
// });

$app->prefix('/api', function (Router $router) {

   // --- Middleware for all routes in this group ---
   $router->middleware(function (Request $req, Response $res, Closure $next) {
      $id = $req->urlParams('id');

      if ($id !== 2) {
         return $res->unauthorized('Unauthorized user');
      }

      $req->user = $id;
      return $next();
   });

   // --- GET Route ---
   $router->get('/user/{id: int}', function (Request $req, Response $res, $next) {
      $user = $req->user;

      return $res->success("Hello from route with ID: $user");
   });

});


$app->prefix('/api', function (Router $router) {

   // --- Global middleware for all routes and prefixes in this parent group ---
   $router->middleware(function ($_, $__, Closure $next) {
      return $next();
   });

   $router->prefix('/v1', function(Router $router) {

      // --- Middleware for all routes in this /v1 group ---
      $router->middleware(function (Request $request, Response $response, Closure $next) {
         $id = $request->urlParams('id');

         if ($id !== 1) {
            return $response
               ->status(Response::StatusUnauthorized)
               ->json(['message' => 'Unauthorized user']);
         }

         $request->user = $id;
         return $next();
      });

      // --- GET Route ---
      $router->get('/users/{id: int}', function (Request $request, Response $response) {
         $user = $request->user;

         return $response->success("Hello from route with ID: $user");
      });

   });
});


$app->run();


$router = new Router('/api', false, []);

$router->get('/status', function(Request $request, Response $response) {
   return $response->json(['status' => 'v2 API is running']);
});


echo '404';
