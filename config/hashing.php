<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Use Argon2id as the preferred password hashing algorithm (PHP 7.3+).
    | Argon2id is the recommended variant, combining Argon2i and Argon2d
    | strengths to resist both GPU and side-channel attacks.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASHING_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options (fallback)
    |--------------------------------------------------------------------------
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2 / Argon2id Options
    |--------------------------------------------------------------------------
    |
    | memory:     memory cost in KB (64 MB recommended for production)
    | time:       number of iterations
    | threads:    degree of parallelism
    |
    */

    'argon' => [
        'memory'  => (int) env('ARGON_MEMORY', 65536),
        'time'    => (int) env('ARGON_TIME', 4),
        'threads' => (int) env('ARGON_THREADS', 1),
        'verify'  => (bool) env('HASH_VERIFY', true),
    ],

];
