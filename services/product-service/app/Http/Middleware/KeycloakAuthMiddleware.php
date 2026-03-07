<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class KeycloakAuthMiddleware
{
    public function __construct(private readonly KeycloakService $keycloakService) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $token = $this->extractBearerToken($request);
        if (! $token) {
            return $this->unauthorizedResponse('Missing or malformed Authorization header');
        }

        try {
            $claims = $this->keycloakService->validateToken($token);
        } catch (RuntimeException $e) {
            Log::warning('KeycloakAuthMiddleware: token validation failed', [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
            ]);
            return $this->unauthorizedResponse($e->getMessage());
        }

        // Attach claims and roles to the request for downstream use
        $request->attributes->set('jwt_claims', $claims);
        $request->attributes->set(
            'jwt_roles',
            $this->keycloakService->extractRoles($claims)
        );
        $request->attributes->set('jwt_sub', $claims->sub ?? 'unknown');

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    private function unauthorizedResponse(string $message): SymfonyResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
