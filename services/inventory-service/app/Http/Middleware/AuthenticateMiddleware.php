<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates JWT tokens issued by the Auth Service.
 * Verifies the token signature locally (if public key is set) or via
 * the auth-service /verify endpoint, then populates request context.
 */
class AuthenticateMiddleware
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorized('No authentication token provided.');
        }

        try {
            $payload = $this->validateToken($token);
        } catch (\Exception $e) {
            Log::warning('Auth token validation failed', ['error' => $e->getMessage()]);
            return $this->unauthorized('Invalid or expired authentication token.');
        }

        if (empty($payload['sub'])) {
            return $this->unauthorized('Token payload is missing subject (sub).');
        }

        $request->attributes->set('auth_user_id',  $payload['sub']);
        $request->attributes->set('auth_tenant_id', $payload['tenant_id'] ?? null);
        $request->attributes->set('auth_roles',     $payload['roles']     ?? []);
        $request->attributes->set('auth_payload',   $payload);

        if (isset($guards[0]) && $guards[0] === 'admin') {
            $roles = $payload['roles'] ?? [];
            if (!in_array('admin', $roles, true) && !in_array('super_admin', $roles, true)) {
                return response()->json([
                    'error'   => 'Forbidden',
                    'message' => 'Insufficient permissions.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return $request->query('token');
    }

    private function validateToken(string $token): array
    {
        $cacheKey = 'auth_token:' . hash('sha256', $token);
        $cached = Cache::store('redis')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Malformed JWT token.');
        }

        $payload = $this->decodePayload($parts[1]);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \RuntimeException('Token has expired.');
        }

        $publicKey = env('JWT_PUBLIC_KEY');
        if ($publicKey) {
            $this->verifySignatureLocally($token, $publicKey);
        } else {
            $payload = $this->verifyWithAuthService($token);
        }

        $ttl = max(0, ($payload['exp'] ?? (time() + 60)) - time());
        Cache::store('redis')->put($cacheKey, $payload, min($ttl, 300));

        return $payload;
    }

    private function decodePayload(string $base64): array
    {
        $padded = str_pad(strtr($base64, '-_', '+/'), strlen($base64) % 4, '=', STR_PAD_RIGHT);
        $decoded = base64_decode($padded, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Cannot decode JWT payload.');
        }

        $data = json_decode($decoded, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JWT payload is not a valid JSON object.');
        }

        return $data;
    }

    private function verifySignatureLocally(string $token, string $publicKey): void
    {
        $parts     = explode('.', $token);
        $algo      = env('JWT_ALGO', 'HS256');
        $secret    = env('JWT_SECRET', '');
        $signingInput = $parts[0] . '.' . $parts[1];

        $expectedSig = match ($algo) {
            'HS256' => hash_hmac('sha256', $signingInput, $secret, true),
            'HS512' => hash_hmac('sha512', $signingInput, $secret, true),
            default => throw new \InvalidArgumentException("Unsupported JWT algorithm: {$algo}"),
        };

        $actualSig = base64_decode(str_pad(strtr($parts[2], '-_', '+/'), strlen($parts[2]) % 4, '=', STR_PAD_RIGHT), true);

        if (!hash_equals($expectedSig, $actualSig)) {
            throw new \RuntimeException('JWT signature verification failed.');
        }
    }

    private function verifyWithAuthService(string $token): array
    {
        $authServiceUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
        $verifyPath     = env('AUTH_SERVICE_VERIFY_TOKEN_PATH', '/api/v1/auth/verify');

        $response = Http::timeout(5)
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get($authServiceUrl . $verifyPath);

        if (!$response->successful()) {
            throw new \RuntimeException('Auth service rejected the token.');
        }

        $data = $response->json();

        if (empty($data['valid'])) {
            throw new \RuntimeException('Token reported as invalid by auth service.');
        }

        return $data['payload'] ?? $this->decodePayload(explode('.', $token)[1]);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'error'   => 'Unauthorized',
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
