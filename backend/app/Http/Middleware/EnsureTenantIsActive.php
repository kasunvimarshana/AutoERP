<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant associated with this user',
            ], 403);
        }

        if (!$tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your tenant account has been suspended. Please contact support.',
            ], 403);
        }

        // Check subscription status
        if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
            ], 403);
        }

        return $next($request);
    }
}
