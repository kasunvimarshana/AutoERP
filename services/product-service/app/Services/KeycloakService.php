<?php

namespace App\Services;

use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use stdClass;

class KeycloakService
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 5,
            'verify'  => app()->isProduction(),
        ]);
    }

    /**
     * Validate a Bearer JWT token against Keycloak JWKS and return the decoded claims.
     *
     * @throws RuntimeException on invalid token
     */
    public function validateToken(string $token): stdClass
    {
        $jwksUri = config('keycloak.jwks_uri');
        $cacheTtl = (int) config('keycloak.jwks_cache_ttl', 3600);

        $jwksData = Cache::remember("keycloak_jwks", $cacheTtl, function () use ($jwksUri) {
            $response = $this->httpClient->get($jwksUri);
            return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        });

        $keys = $this->buildKeys($jwksData);

        try {
            $decoded = JWT::decode($token, $keys);
        } catch (\Throwable $e) {
            throw new RuntimeException('JWT validation failed: '.$e->getMessage(), 401, $e);
        }

        $this->assertRealmAndAudience($decoded);

        return $decoded;
    }

    /**
     * Obtain a service-account access token from Keycloak (client_credentials grant).
     */
    public function getServiceAccountToken(): string
    {
        $cacheKey = 'keycloak_service_account_token';

        return Cache::remember($cacheKey, 240, function () {
            $tokenUrl = config('keycloak.token_url');

            $response = $this->httpClient->post($tokenUrl, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => config('keycloak.service_account.client_id'),
                    'client_secret' => config('keycloak.service_account.client_secret'),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($body['access_token'])) {
                throw new RuntimeException('Keycloak service account token response missing access_token');
            }

            return $body['access_token'];
        });
    }

    /**
     * Extract tenant_id from JWT claims (custom claim or organization).
     */
    public function extractTenantId(stdClass $claims): string
    {
        // Support both custom claim and Keycloak organization-based tenancy
        return $claims->tenant_id
            ?? $claims->organization_id
            ?? throw new RuntimeException('tenant_id claim not found in JWT');
    }

    /**
     * Extract roles from JWT resource_access claims.
     */
    public function extractRoles(stdClass $claims): array
    {
        $clientId = config('keycloak.client_id');
        return $claims->resource_access?->{$clientId}?->roles ?? $claims->realm_access?->roles ?? [];
    }

    private function buildKeys(array $jwks): array
    {
        $keys = [];
        foreach ($jwks['keys'] ?? [] as $key) {
            if (empty($key['kid']) || empty($key['alg'])) {
                continue;
            }
            $keys[$key['kid']] = new Key(
                $this->convertJwkToPublicKey($key),
                $key['alg']
            );
        }
        return $keys;
    }

    private function convertJwkToPublicKey(array $key): string
    {
        if (! empty($key['x5c'][0])) {
            return "-----BEGIN CERTIFICATE-----\n"
                .chunk_split($key['x5c'][0], 64, "\n")
                ."-----END CERTIFICATE-----\n";
        }
        // For RS256 keys, reconstruct from n/e
        if ($key['kty'] === 'RSA' && ! empty($key['n']) && ! empty($key['e'])) {
            return $this->rsaJwkToPem($key['n'], $key['e']);
        }
        throw new RuntimeException('Cannot convert JWK to public key: unsupported key format');
    }

    private function rsaJwkToPem(string $n, string $e): string
    {
        $modulus  = \Firebase\JWT\JWT::urlsafeB64Decode($n);
        $exponent = \Firebase\JWT\JWT::urlsafeB64Decode($e);

        $components = [
            'modulus'  => pack('Ca*a*', 2, $this->asn1Length(strlen($modulus)), $modulus),
            'exponent' => pack('Ca*a*', 2, $this->asn1Length(strlen($exponent)), $exponent),
        ];

        $rsaPublicKey = pack(
            'Ca*a*a*',
            48,
            $this->asn1Length(strlen($components['modulus']) + strlen($components['exponent'])),
            $components['modulus'],
            $components['exponent']
        );

        $rsaOid = pack('H*', '300d06092a864886f70d0101010500');
        $rsaSequence = pack('Ca*a*', 3, $this->asn1Length(strlen($rsaPublicKey) + 1), "\0".$rsaPublicKey);
        $publicKey = pack('Ca*a*a*', 48, $this->asn1Length(strlen($rsaOid) + strlen($rsaSequence)), $rsaOid, $rsaSequence);

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($publicKey), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private function asn1Length(int $length): string
    {
        if ($length <= 127) {
            return chr($length);
        }
        $temp = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($temp)).$temp;
    }

    private function assertRealmAndAudience(stdClass $claims): void
    {
        $expectedIssuer = config('keycloak.base_url').'/realms/'.config('keycloak.realm');
        if (($claims->iss ?? '') !== $expectedIssuer) {
            throw new RuntimeException('JWT issuer mismatch');
        }
    }
}
