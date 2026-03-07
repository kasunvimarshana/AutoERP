<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * API routes are stateless and don't use CSRF tokens.
     *
     * @var list<string>
     */
    protected $except = [
        'api/*',
    ];
}
