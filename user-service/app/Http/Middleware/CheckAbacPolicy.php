<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAbacPolicy
{
    /**
     * An example Attribute-Based Access Control (ABAC) middleware for User Service.
     * Evaluates rules against Token Attributes.
     */
    public function handle(Request $request, Closure $next, string $policyName): Response
    {
        $department = $request->attributes->get('auth_department');

        switch ($policyName) {
            case 'can_manage_users':
                // ABAC Condition: Only HR or IT can manage users globally
                if (!in_array($department, ['hr', 'it'])) {
                    return response()->json(['error' => 'ABAC violation: User management restricted to HR or IT.'], 403);
                }
                break;
        }

        return $next($request);
    }
}
