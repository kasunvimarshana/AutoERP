<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeycloakService
{
    private string $baseUrl;
    private string $realm;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('keycloak.base_url');
        $this->realm = config('keycloak.realm');
        $this->clientId = config('keycloak.client_id');
        $this->clientSecret = config('keycloak.client_secret');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);

            if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            Log::error('Token validation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function extractRoles(array $payload): array
    {
        $roles = [];

        if (isset($payload['realm_access']['roles'])) {
            $roles = array_merge($roles, $payload['realm_access']['roles']);
        }

        if (isset($payload['resource_access'][$this->clientId]['roles'])) {
            $roles = array_merge($roles, $payload['resource_access'][$this->clientId]['roles']);
        }

        return array_unique($roles);
    }

    public function getServiceToken(): ?string
    {
        return Cache::remember('service_token', 300, function () {
            try {
                $response = Http::asForm()->post(
                    "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token",
                    [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ]
                );

                if ($response->successful()) {
                    return $response->json('access_token');
                }
            } catch (\Exception $e) {
                Log::error('Failed to get service token: ' . $e->getMessage());
            }
            return null;
        });
    }
}
