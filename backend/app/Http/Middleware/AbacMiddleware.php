<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ABAC (Attribute-Based Access Control) middleware.
 *
 * Evaluates resource attribute conditions defined in config/abac.php
 * against the authenticated user and the current request context.
 *
 * Example rule in config/abac.php:
 *   'products.update' => [
 *       ['attribute' => 'tenant_id', 'operator' => '=', 'user_attribute' => 'tenant_id'],
 *   ]
 *
 * Usage in routes:
 *   ->middleware('abac:products.update')
 */
final class AbacMiddleware
{
    public function handle(Request $request, Closure $next, string $policy): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Super-admin bypasses all ABAC rules.
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $rules = config("abac.{$policy}", []);

        foreach ($rules as $rule) {
            if (!$this->evaluateRule($user, $request, $rule)) {
                return response()->json([
                    'error'  => 'Access denied by policy.',
                    'policy' => $policy,
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Evaluate a single ABAC rule against the user and request.
     *
     * Supported operators: =, !=, in, not_in, >, <, >=, <=
     */
    private function evaluateRule(\Illuminate\Foundation\Auth\User $user, Request $request, array $rule): bool
    {
        $attribute     = $rule['attribute'];
        $operator      = $rule['operator'] ?? '=';
        $userAttribute = $rule['user_attribute'] ?? null;
        $value         = $rule['value'] ?? ($userAttribute ? data_get($user, $userAttribute) : null);

        // Try resolving the attribute from the route model, request input, or route params.
        $resourceValue = $request->route($attribute)
            ?? $request->input($attribute)
            ?? null;

        if ($resourceValue === null) {
            return true; // Cannot evaluate; allow and let the policy handle it.
        }

        return match ($operator) {
            '='      => $resourceValue == $value,
            '!='     => $resourceValue != $value,
            'in'     => in_array($resourceValue, (array) $value),
            'not_in' => !in_array($resourceValue, (array) $value),
            '>'      => $resourceValue > $value,
            '<'      => $resourceValue < $value,
            '>='     => $resourceValue >= $value,
            '<='     => $resourceValue <= $value,
            default  => false,
        };
    }
}
