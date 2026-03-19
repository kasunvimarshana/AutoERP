<?php

namespace Enterprise\Core\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * ServiceAuthHelper - Handles Service-to-Service (S2S) authentication.
 * Uses OAuth2 Client Credentials grant for internal machine-to-machine calls.
 */
class ServiceAuthHelper
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $authServiceUrl;

    public function __construct()
    {
        $this->clientId = config('enterprise.auth.client_id');
        $this->clientSecret = config('enterprise.auth.client_secret');
        $this->authServiceUrl = config('enterprise.auth.url');
    }

    /**
     * Get a machine-to-machine access token.
     * Caches the token to avoid repeated calls to the Auth Service.
     */
    public function getServiceToken(): string
    {
        $cacheKey = "s2s_token_" . $this->clientId;

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::post("{$this->authServiceUrl}/oauth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => '*', // Full scope for internal services
            ]);

            if ($response->failed()) {
                throw new \Exception("Failed to obtain service token: " . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Helper to perform an internal authenticated request.
     */
    public function internalCall(string $method, string $url, array $data = [])
    {
        return Http::withToken($this->getServiceToken())
            ->{$method}($url, $data);
    }
}
