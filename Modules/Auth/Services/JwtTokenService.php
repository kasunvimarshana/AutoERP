<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Auth\Exceptions\TokenExpiredException;
use Modules\Auth\Exceptions\TokenInvalidException;
use Modules\Auth\Models\RevokedToken;

/**
 * JwtTokenService
 *
 * Native Laravel implementation of JWT token service
 * No external dependencies - uses PHP's built-in JWT support
 */
class JwtTokenService implements TokenServiceInterface
{
    protected string $secret;

    protected string $algorithm = 'HS256';

    protected int $ttl;

    protected int $refreshTtl;

    public function __construct()
    {
        $this->secret = config('jwt.secret', config('app.key'));
        $this->ttl = (int) config('jwt.ttl', 3600); // 1 hour
        $this->refreshTtl = (int) config('jwt.refresh_ttl', 86400); // 24 hours
    }

    public function generate(
        string $userId,
        string $deviceId,
        ?string $organizationId = null,
        ?string $tenantId = null,
        array $claims = []
    ): string {
        $now = new DateTimeImmutable;
        $expiresAt = $now->modify("+{$this->ttl} seconds");

        $tokenId = $this->generateTokenId();

        $payload = array_merge([
            'jti' => $tokenId,
            'iss' => config('app.url'),
            'iat' => $now->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'sub' => $userId,
            'device_id' => $deviceId,
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
        ], $claims);

        return $this->encode($payload);
    }

    public function validate(string $token): ?array
    {
        try {
            $payload = $this->decode($token);

            // Check if token is expired
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new TokenExpiredException('Token has expired');
            }

            // Check if token is revoked
            if (isset($payload['jti']) && $this->isRevoked($payload['jti'])) {
                throw new TokenInvalidException('Token has been revoked');
            }

            return $payload;
        } catch (TokenExpiredException|TokenInvalidException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TokenInvalidException('Invalid token: '.$e->getMessage());
        }
    }

    /**
     * Get claims from token without full validation
     * Useful for extracting information from tokens
     */
    public function getClaims(string $token): array
    {
        try {
            return $this->decode($token);
        } catch (\Exception $e) {
            throw new TokenInvalidException('Invalid token: '.$e->getMessage());
        }
    }

    public function refresh(string $token): ?string
    {
        try {
            $payload = $this->validate($token);
        } catch (TokenExpiredException $e) {
            // Allow refresh for expired tokens within refresh window
            $payload = $this->decode($token);
        }

        if (! $payload) {
            return null;
        }

        // Check if token is within refresh window
        $issuedAt = $payload['iat'] ?? 0;
        if (time() - $issuedAt > $this->refreshTtl) {
            return null;
        }

        // Revoke old token
        if (isset($payload['jti'])) {
            $this->revoke($token);
        }

        // Generate new token
        return $this->generate(
            $payload['sub'],
            $payload['device_id'],
            $payload['organization_id'] ?? null,
            $payload['tenant_id'] ?? null,
            array_diff_key($payload, array_flip([
                'jti', 'iss', 'iat', 'exp', 'sub', 'device_id', 'organization_id', 'tenant_id',
            ]))
        );
    }

    public function revoke(string $token): bool
    {
        try {
            $payload = $this->decode($token);
            $tokenId = $payload['jti'] ?? null;
            $expiresAt = $payload['exp'] ?? null;

            if (! $tokenId || ! $expiresAt) {
                return false;
            }

            // Store in database for persistent revocation
            RevokedToken::create([
                'token_id' => $tokenId,
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            ]);

            // Also cache for quick lookup
            $ttl = $expiresAt - time();
            if ($ttl > 0) {
                Cache::put("revoked_token:{$tokenId}", true, $ttl);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isRevoked(string $tokenId): bool
    {
        // Check cache first
        if (Cache::has("revoked_token:{$tokenId}")) {
            return true;
        }

        // Check database
        return RevokedToken::where('token_id', $tokenId)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Encode payload to JWT
     */
    protected function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->sign("{$headerEncoded}.{$payloadEncoded}");

        return "{$headerEncoded}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Decode JWT to payload
     */
    protected function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        [$headerEncoded, $payloadEncoded, $signature] = $parts;

        // Verify signature
        $expectedSignature = $this->sign("{$headerEncoded}.{$payloadEncoded}");
        if (! hash_equals($expectedSignature, $signature)) {
            throw new \InvalidArgumentException('Invalid token signature');
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid token payload');
        }

        return $payload;
    }

    /**
     * Sign the data
     */
    protected function sign(string $data): string
    {
        $signature = hash_hmac('sha256', $data, $this->secret, true);

        return $this->base64UrlEncode($signature);
    }

    /**
     * Base64 URL encode
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generate a unique token ID
     */
    protected function generateTokenId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
