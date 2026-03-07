<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PassportTokenValidationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithPassport
{
    public function __construct(
        private readonly PassportTokenValidationService $tokenService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokenData = $this->tokenService->validate($token);

        if (!$tokenData) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        // Find or create local user representation
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tokenData['tenant_id'])
            ->where('email', $tokenData['email'])
            ->first();

        if (!$user) {
            // Auto-provision a local user shadow record from the token. Authentication is
            // always delegated to the auth-service; the random password is never used.
            app()->instance('current_tenant_id', $tokenData['tenant_id']);
            $user = User::create([
                'tenant_id' => $tokenData['tenant_id'],
                'name' => $tokenData['name'],
                'email' => $tokenData['email'],
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                'roles' => $tokenData['roles'] ?? ['staff'],
                'attributes' => $tokenData['attributes'] ?? [],
                'is_active' => true,
                'auth_user_id' => $tokenData['user_id'],
            ]);
        } else {
            // Sync roles and attributes from token
            $user->update([
                'roles' => $tokenData['roles'] ?? $user->roles,
                'attributes' => $tokenData['attributes'] ?? $user->attributes,
            ]);
        }

        $request->setUserResolver(fn() => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
