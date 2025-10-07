<?php

require_once '../../vendor/autoload.php';

use phpSPA\Http\Request;
use phpSPA\Http\Response;

response()->get('/user/{id: int}', function (Request $request, $id) {
    $user = 2;

    return response(['message' => "Hello from route with ID: $id", 'data' => $user], 200)
        ->header('X-Route-Header', 'route_value');
});

response()->get('/status', fn (): Response => response()::json([
    'status' => 'OK',
    'message' => 'Server is running.'
]));


response()->get('/success', fn (): Response => response()->success(['result' => 'data'], 'Operation successful'));

response()->get('/error', fn (): Response => response()->error('Something went wrong', 500));

response()->get('/data', fn (): Response => response()
    ->json(['data' => 'some data'])
    ->header('X-Custom-Header', 'Value')
    ->contentType('application/json'));
