<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * When using credentials, we must specify the exact origins allowed.
     * A wildcard ('*') is not permitted.
     */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * This is the most important change. Your frontend is sending
     * requests with credentials (withCredentials: true), so the
     * backend must explicitly allow it by setting this to true.
     */
    'supports_credentials' => true,

];
