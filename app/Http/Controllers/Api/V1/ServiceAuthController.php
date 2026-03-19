<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Services\PublicKeyService;
use App\Services\ServiceAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

final class ServiceAuthController extends Controller
{
    public function __construct(
        private readonly ServiceAuthService $serviceAuthService,
        private readonly PublicKeyService $publicKeyService,
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/service/register",
     *     summary="Register a new service client",
     *     tags={"Service Auth"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_name'   => ['required', 'string', 'max:100'],
            'allowed_scopes' => ['sometimes', 'array'],
            'allowed_ips'    => ['sometimes', 'array'],
            'expires_at'     => ['sometimes', 'nullable', 'date'],
        ]);

        $result = $this->serviceAuthService->registerService(
            serviceName: $validated['service_name'],
            options: array_filter([
                'allowed_scopes' => $validated['allowed_scopes'] ?? [],
                'allowed_ips'    => $validated['allowed_ips'] ?? null,
                'expires_at'     => isset($validated['expires_at'])
                    ? \Carbon\Carbon::parse($validated['expires_at'])
                    : null,
            ])
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'client_id'     => $result['service_token']->client_id,
                'service_name'  => $result['service_token']->service_name,
                'client_secret' => $result['plain_secret'], // shown only once
            ],
            'message' => 'Service registered. Store the client_secret securely – it will not be shown again.',
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/service/token",
     *     summary="Exchange client credentials for a service JWT",
     *     tags={"Service Auth"}
     * )
     */
    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'     => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        try {
            $token = $this->serviceAuthService->issueServiceJwt(
                $validated['client_id'],
                $validated['client_secret']
            );

            $ttl = (int) config('sso.token.service_token_ttl_minutes', 60);

            return response()->json([
                'success' => true,
                'data'    => [
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                    'expires_in'   => $ttl * 60,
                ],
            ]);
        } catch (AuthException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'AUTH_ERROR', 'message' => $e->getMessage()],
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/public-key",
     *     summary="Retrieve RS256 public key for local JWT verification by microservices",
     *     tags={"Auth"}
     * )
     */
    public function publicKey(): JsonResponse
    {
        try {
            $publicKey   = $this->publicKeyService->getPublicKey();
            $fingerprint = $this->publicKeyService->getPublicKeyFingerprint();

            return response()->json([
                'success' => true,
                'data'    => [
                    'public_key'  => $publicKey,
                    'algorithm'   => 'RS256',
                    'fingerprint' => $fingerprint,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'KEY_NOT_FOUND', 'message' => $e->getMessage()],
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
