<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PassportTokenValidationService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => config('services.auth_service.url'), 'timeout' => 5]);
    }

    public function validate(string $bearerToken): ?array
    {
        $cacheKey = 'token_introspect_'.hash('sha256', $bearerToken);

        return Cache::remember($cacheKey, 60, function () use ($bearerToken) {
            try {
                $response = $this->http->post(config('services.auth_service.introspection_endpoint'), [
                    'json' => ['token' => $bearerToken],
                    'headers' => [
                        'X-Service-Secret' => config('services.auth_service.shared_secret'),
                        'Accept' => 'application/json',
                    ],
                ]);
                $data = json_decode($response->getBody()->getContents(), true);

                return ($data['active'] ?? false) ? $data : null;
            } catch (RequestException $e) {
                Log::warning('Token validation failed', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }
}
