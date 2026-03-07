<?php
namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizationMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$user->hasPermissionTo($permission)) {
            if (!$this->checkAttributes($user, $permission, $request)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        return $next($request);
    }

    private function checkAttributes($user, string $permission, Request $request): bool
    {
        $attributes = $user->attributes ?? [];

        if (isset($attributes['is_department_head']) && $attributes['is_department_head']) {
            return true;
        }

        $tenantId = app('tenant')?->id ?? null;
        if ($tenantId && $user->tenant_id === $tenantId) {
            $resourcePermissions = $attributes['extra_permissions'] ?? [];
            if (in_array($permission, $resourcePermissions)) {
                return true;
            }
        }

        return false;
    }
}
