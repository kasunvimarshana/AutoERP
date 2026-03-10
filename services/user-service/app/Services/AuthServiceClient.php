<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AuthServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8001'), '/');
    }

    public function validateToken(string $token): array
    {
        try {
            $response = Http::timeout(5)
                ->withToken($token)
                ->post("{$this->baseUrl}/api/auth/validate");

            if ($response->successful()) {
                return $response->json();
            }

            throw new RuntimeException('Token validation failed: ' . $response->status());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('Auth service unavailable: ' . $e->getMessage());
        }
    }
}
