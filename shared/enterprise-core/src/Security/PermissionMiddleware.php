<?php

namespace Enterprise\Core\Security;

use Closure;
use Illuminate\Http\Request;

/**
 * PermissionMiddleware - Enforces RBAC on a per-route basis.
 * Usage in routes: ->middleware('permission:inventory.view')
 */
class PermissionMiddleware
{
    protected AuthorizationContract $auth;

    public function __construct(AuthorizationContract $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // 1. Context extraction for ABAC (Optional)
        // If the request body has an 'amount' or other relevant attributes,
        // we can pass them into the `can()` check.
        $context = $request->only(['amount', 'warehouse_id', 'department_id']);

        // 2. Enforce Permission Check
        if (!$this->auth->can($permission, $context)) {
            return response()->json([
                'error' => 'Forbidden: You do not have permission to perform this action.',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
