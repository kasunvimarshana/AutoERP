<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RBACMiddleware
{
    /**
     * Handle Role-Based Access Control.
     *
     * Usage in routes: ->middleware('rbac:role_name')
     *                  ->middleware('rbac:role1|role2')   (any of these roles)
     *                  ->middleware('rbac:role1,role2')   (both roles required)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'error'   => 'UNAUTHENTICATED',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tenant = $request->attributes->get('tenant');
        $teamId = $tenant?->id;

        foreach ($roles as $roleExpression) {
            // Pipe-separated means ANY of those roles
            if (str_contains($roleExpression, '|')) {
                $anyRoles = explode('|', $roleExpression);
                $hasAny = collect($anyRoles)->contains(
                    fn (string $r) => $user->hasRole($r, $teamId)
                );
                if (!$hasAny) {
                    return $this->forbidden($roleExpression);
                }
                continue;
            }

            // Single role — user must have it
            if (!$user->hasRole($roleExpression, $teamId)) {
                return $this->forbidden($roleExpression);
            }
        }

        return $next($request);
    }

    private function forbidden(string $role): Response
    {
        return response()->json([
            'success' => false,
            'message' => "Insufficient role. Required: {$role}",
            'error'   => 'INSUFFICIENT_ROLE',
        ], Response::HTTP_FORBIDDEN);
    }
}
