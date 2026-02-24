<?php
return [
    'access_token_ttl' => env('AUTH_ACCESS_TOKEN_TTL', 900),
    'refresh_token_ttl' => env('AUTH_REFRESH_TOKEN_TTL', 2592000),
];
