<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if account is active
        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        // Check if account is locked
        if ($user->isLocked()) {
            $minutes = now()->diffInMinutes($user->locked_until);
            
            return response()->json([
                'success' => false,
                'message' => "Your account is locked. Please try again in {$minutes} minutes.",
                'locked_until' => $user->locked_until->toIso8601String(),
            ], 403);
        }

        // Check email verification if required
        if (config('auth.require_email_verification', false) && !$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before continuing.',
                'requires_verification' => true,
            ], 403);
        }

        return $next($request);
    }
}
