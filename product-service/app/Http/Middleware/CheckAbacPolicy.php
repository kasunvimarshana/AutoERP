<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAbacPolicy
{
    /**
     * An example Attribute-Based Access Control (ABAC) middleware.
     * Evaluates rules against Token Attributes (e.g. department) and Environment Context (e.g. Time/IP).
     */
    public function handle(Request $request, Closure $next, string $policyName): Response
    {
        $department = $request->attributes->get('auth_department');

        switch ($policyName) {
            case 'can_create_product':
                // ABAC Condition: Only allow engineering department to POST during working hours
                if ($department !== 'engineering') {
                    return response()->json(['error' => 'ABAC violation: Only engineering can create products.'], 403);
                }
                $hour = now()->hour;
                if ($hour < 8 || $hour > 18) {
                    return response()->json(['error' => 'ABAC violation: Actions not permitted after hours.'], 403);
                }
                break;

            case 'can_delete_product':
                // ABAC Condition: Complex ownership mapped internally could go here.
                if (!in_array('admin', $request->attributes->get('auth_roles'))) {
                    return response()->json(['error' => 'ABAC violation: Must hold global admin.'], 403);
                }
                break;
        }

        return $next($request);
    }
}
