<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $orgId = $request->header('X-Organization-ID')
            ?? $request->route('organization')
            ?? $user->organization_id;

        if ($orgId && $user->tenant_id) {
            $belongs = $user->organizations()
                ->where('organizations.id', $orgId)
                ->where('organizations.tenant_id', $user->tenant_id)
                ->exists();

            if (! $belongs && $user->organization_id !== $orgId) {
                return response()->json(['message' => 'Forbidden: Organization access denied.'], 403);
            }
        }

        return $next($request);
    }
}
