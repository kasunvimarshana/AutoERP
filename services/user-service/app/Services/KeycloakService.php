<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeycloakService
{
    public function validateToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(
                base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)),
                true
            );
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
        $clientId = config('keycloak.client_id');
        if (isset($payload['resource_access'][$clientId]['roles'])) {
            $roles = array_merge($roles, $payload['resource_access'][$clientId]['roles']);
        }
        return array_unique($roles);
    }

    public function getServiceToken(): ?string
    {
        $baseUrl = config('keycloak.base_url');
        $realm = config('keycloak.realm');
        $clientId = config('keycloak.client_id');
        $clientSecret = config('keycloak.client_secret');
        return Cache::remember('service_token', 300, function () use ($baseUrl, $realm, $clientId, $clientSecret) {
            try {
                $response = Http::asForm()->post(
                    "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token",
                    ['grant_type' => 'client_credentials', 'client_id' => $clientId, 'client_secret' => $clientSecret]
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
