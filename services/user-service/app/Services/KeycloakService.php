<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;
use UnexpectedValueException;

class KeycloakService
{
    private string $baseUrl;
    private string $realm;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('keycloak.base_url'), '/');
        $this->realm   = (string) config('keycloak.realm');
    }

    /*
    |--------------------------------------------------------------------------
    | Token Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the Bearer token and return decoded claims.
     *
     * @throws \RuntimeException on invalid token
     */
    public function validateToken(string $token): stdClass
    {
        $keys = $this->getJwks();

        try {
            $algorithms = config('keycloak.algorithms', ['RS256']);
            $decoded    = JWT::decode($token, JWK::parseKeySet($keys), $algorithms);
        } catch (\Throwable $e) {
            Log::warning('Keycloak JWT validation failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Invalid or expired token: '.$e->getMessage(), 401, $e);
        }

        return $decoded;
    }

    /*
    |--------------------------------------------------------------------------
    | Claims Extraction
    |--------------------------------------------------------------------------
    */

    public function extractTenantId(stdClass $claims): ?string
    {
        $key = config('keycloak.claims.tenant_id', 'tenant_id');

        return $claims->{$key} ?? null;
    }

    public function extractUserId(stdClass $claims): ?string
    {
        $key = config('keycloak.claims.user_id', 'sub');

        return $claims->{$key} ?? null;
    }

    /**
     * @return string[]
     */
    public function extractRoles(stdClass $claims): array
    {
        // Support both realm_access.roles and a flat roles array
        if (isset($claims->realm_access->roles)) {
            return (array) $claims->realm_access->roles;
        }

        if (isset($claims->roles)) {
            return (array) $claims->roles;
        }

        return [];
    }

    public function extractEmail(stdClass $claims): ?string
    {
        return $claims->email ?? null;
    }

    public function extractUsername(stdClass $claims): ?string
    {
        return $claims->preferred_username ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | RBAC / ABAC
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether the decoded claims contain the required role.
     */
    public function hasRole(stdClass $claims, string $requiredRole): bool
    {
        $roles       = $this->extractRoles($claims);
        $hierarchy   = config('tenant.roles', []);

        if (! isset($hierarchy[$requiredRole])) {
            return in_array($requiredRole, $roles, true);
        }

        $requiredLevel = $hierarchy[$requiredRole];

        foreach ($roles as $role) {
            if (isset($hierarchy[$role]) && $hierarchy[$role] >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attribute-based check: confirm a claim matches an expected value.
     */
    public function checkAttribute(stdClass $claims, string $attribute, mixed $expectedValue): bool
    {
        $actual = $claims->{$attribute} ?? null;

        return $actual === $expectedValue;
    }

    /*
    |--------------------------------------------------------------------------
    | JWKS Fetching (with cache)
    |--------------------------------------------------------------------------
    */

    private function getJwks(): array
    {
        $cacheKey = "keycloak_jwks_{$this->realm}";
        $ttl      = (int) config('keycloak.token_cache_ttl', 300);

        return Cache::remember($cacheKey, $ttl, function () {
            $url = "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/certs";

            $response = Http::timeout(5)->get($url);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "Failed to fetch JWKS from Keycloak: HTTP {$response->status()}"
                );
            }

            return $response->json();
        });
    }
}
