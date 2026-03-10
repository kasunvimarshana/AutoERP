<?php

return [
    'secret' => env('APP_KEY'),
    'ttl' => 3600,          // access token lifetime in seconds (1 hour)
    'refresh_ttl' => 604800, // refresh token lifetime in seconds (7 days)
];
