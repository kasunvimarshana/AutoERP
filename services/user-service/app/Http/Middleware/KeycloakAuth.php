<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\UserService;
use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class KeycloakAuth
{
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(private readonly UserService $userService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            return response()->json(['message' => 'Unauthorized: No token provided.'], 401);
        }

        try {
            $payload = $this->validateToken($token);

            $roles = $this->extractRoles($payload);

            // Attach the decoded claims to the request for downstream use
            $request->attributes->set('jwt_payload', $payload);
            $request->attributes->set('user_id', $payload->sub ?? null);
            $request->attributes->set('roles', $roles);

            // Auto-sync the user record from Keycloak JWT claims
            if (! empty($payload->sub)) {
                $this->syncUser($payload, $roles);
            }
        } catch (Throwable $e) {
            Log::warning('Keycloak token validation failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Unauthorized: ' . $e->getMessage()], 401);
        }

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

    private function validateToken(string $token): object
    {
        $jwks = $this->getPublicKeys();

        $keys = [];
        foreach ($jwks['keys'] as $keyData) {
            $kid        = $keyData['kid'] ?? 'default';
            $keys[$kid] = JWK::parseKey($keyData);
        }

        $decoded = JWT::decode($token, $keys);

        $this->validateClaims($decoded);

        return $decoded;
    }

    private function validateClaims(object $claims): void
    {
        $issuer = config('keycloak.base_url') . '/realms/' . config('keycloak.realm');

        if (($claims->iss ?? '') !== $issuer) {
            throw new \RuntimeException('Invalid token issuer.');
        }

        $audience  = config('keycloak.client_id');
        $tokenAud  = $claims->aud ?? '';
        $audiences = is_array($tokenAud) ? $tokenAud : [$tokenAud];

        if (! in_array($audience, $audiences, true)) {
            throw new \RuntimeException('Invalid token audience.');
        }
    }

    private function getPublicKeys(): array
    {
        return Cache::remember('keycloak_jwks', self::CACHE_TTL_SECONDS, function (): array {
            $url = config('keycloak.base_url')
                . '/realms/'
                . config('keycloak.realm')
                . '/protocol/openid-connect/certs';

            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                throw new \RuntimeException('Could not fetch Keycloak public keys.');
            }

            return $response->json();
        });
    }

    /**
     * @return array<string>
     */
    private function extractRoles(object $payload): array
    {
        $realmRoles  = $payload->realm_access->roles ?? [];
        $clientId    = config('keycloak.client_id');
        $clientRoles = $payload->resource_access->{$clientId}->roles ?? [];

        return array_unique(array_merge((array) $realmRoles, (array) $clientRoles));
    }

    /**
     * Create or update the local user record from the JWT claims.
     *
     * @param  array<string> $roles
     */
    private function syncUser(object $payload, array $roles): void
    {
        try {
            $this->userService->syncFromKeycloak((string) $payload->sub, [
                'email'         => $payload->email ?? null,
                'first_name'    => $payload->given_name ?? null,
                'last_name'     => $payload->family_name ?? null,
                'username'      => $payload->preferred_username ?? null,
                'roles'         => $roles,
                'is_active'     => true,
                'last_login_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Never let a sync failure block the request
            Log::warning('User sync from Keycloak failed', [
                'sub'   => $payload->sub ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
