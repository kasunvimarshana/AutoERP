<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * For an API-only gateway, return null so a JSON 401 is returned instead.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
