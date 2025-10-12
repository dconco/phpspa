<?php

return [
    /*
     * Specific domains that are allowed to access your resources.
     */
    'allow_origin' => '*',

    /*
     * The HTTP methods that are allowed for CORS requests.
     */
    'allow_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'VIEW',
        'OPTIONS',
    ],

    /*
     * Headers that are allowed in CORS requests.
     */
    'allow_headers' => [
        'Accept',
        'Origin',
        'Content-Type',
        'Authorization',
        'X-Api-Key',
        'X-Csrf-Token',
        'X-Requested-With',
        'X-Phpspa-Target',
    ],

    /*
     * Headers that browsers are allowed to access.
     */
    'expose_headers' => ['Content-Length', 'Content-Range', 'X-Custom-Header'],

    /*
     * The maximum time (in seconds) the results of a preflight request can be cached.
     */
    'max_age' => 7200,

    /*
     * Indicates whether the request can include user credentials.
     */
    'allow_credentials' => true,

    /*
     * Another toggle for allowing credentials, ensuring clarity.
     */
    'supports_credentials' => true,
];
