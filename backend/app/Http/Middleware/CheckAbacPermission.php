<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAbacPermission
{
    public function handle(Request $request, Closure $next, string $permission = ''): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($permission && !$this->checkAbacPermission($user, $permission)) {
            return response()->json(['error' => 'Access denied by ABAC policy'], 403);
        }

        return $next($request);
    }

    private function checkAbacPermission($user, string $permission): bool
    {
        if ($user->hasPermissionTo($permission)) {
            return true;
        }

        $attributes = $user->abac_attributes ?? [];
        return isset($attributes[$permission]) && $attributes[$permission] === true;
    }
}
