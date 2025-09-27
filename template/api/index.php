<?php

require_once '../../vendor/autoload.php';

use phpSPA\Http\Response;
use phpSPA\Http\Request;

use function phpSPA\Http\response;

$request = new Request();
$response = Response::fromRequest($request)->caseSensitive();

$res = response();

$res->get('/test', function ($request) {
    return response('Hello from /test', 200)
        ->header('X-Custom-Header', 'Value')
        ->contentType('text/plain');
});

// Define your routes
$response->get('/user/{id: int}', function ($request, $id) {
    $user = 2; // This would be your actual user lookup
    return response(['message' => 'Hello from route with ID: ' . $id, 'data' => $user], 200)
        ->header('X-Route-Header', 'route_value');
});

$response->get('/status', function ($request) {
    return response()->json([
        'status' => 'OK',
        'message' => 'Server is running.'
    ]);
});


$response->get('/data', function ($request) {
    return response()
        ->json(['data' => 'some data'])
        ->header('X-Custom-Header', 'Value')
        ->contentType('application/json');
});

// 6. Using convenience methods
$response->get('/success', function ($request) {
    return response()->success(['result' => 'data'], 'Operation successful');
});

$response->get('/error', function ($request) {
    return response()->error('Something went wrong', 500);
});
