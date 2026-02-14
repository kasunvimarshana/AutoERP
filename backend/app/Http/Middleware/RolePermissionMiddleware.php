<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for Role-Based Access Control and Attribute-Based Access Control
 * 
 * @package App\Http\Middleware
 */
class RolePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permission
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        if (!$request->user()->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action'
            ], 403);
        }
        
        return $next($request);
    }
}
