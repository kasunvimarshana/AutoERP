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

        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tokenData['tenant_id'])
            ->where('email', $tokenData['email'])
            ->first();

        if (!$user) {
            // Auto-provision a local shadow user from the auth-service token payload.
            // This is intentional: the inventory-service does not manage user registration;
            // it mirrors user identity on first access so policies and audit trails work locally.
            app()->instance('current_tenant_id', $tokenData['tenant_id']);
            $user = User::create([
                'tenant_id' => $tokenData['tenant_id'],
                'name' => $tokenData['name'],
                'email' => $tokenData['email'],
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                'roles' => $tokenData['roles'] ?? ['staff'],
                'attributes' => $tokenData['attributes'] ?? [],
                'is_active' => true,
            ]);
        }

        $request->setUserResolver(fn() => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
