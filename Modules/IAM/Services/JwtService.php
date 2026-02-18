<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Native JWT Service
 * 
 * Implements JWT token generation and validation without external packages.
 * Uses Laravel's native encryption and caching for security.
 */
class JwtService
{
    private const ALGORITHM = 'HS256';
    private const TOKEN_TYPE = 'Bearer';
    
    /**
     * Generate JWT token for user
     */
    public function generateToken(User $user, ?int $tenantId = null): array
    {
        $issuedAt = now()->timestamp;
        $expiresAt = now()->addMinutes(config('iam.jwt.access_token_ttl', 60))->timestamp;
        $refreshExpiresAt = now()->addDays(config('iam.jwt.refresh_token_ttl', 7))->timestamp;
        
        // Access token payload
        $payload = [
            'iss' => config('app.url'), // Issuer
            'sub' => $user->id, // Subject (user ID)
            'iat' => $issuedAt, // Issued at
            'exp' => $expiresAt, // Expiration time
            'tenant_id' => $tenantId ?? $user->tenant_id,
            'email' => $user->email,
            'jti' => Str::uuid()->toString(), // JWT ID (unique identifier)
        ];
        
        // Generate access token
        $accessToken = $this->encodeToken($payload);
        
        // Generate refresh token with longer expiry
        $refreshPayload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $issuedAt,
            'exp' => $refreshExpiresAt,
            'type' => 'refresh',
            'jti' => Str::uuid()->toString(),
        ];
        
        $refreshToken = $this->encodeToken($refreshPayload);
        
        // Store token metadata for revocation capability
        $this->storeTokenMetadata($user->id, $payload['jti'], $expiresAt);
        $this->storeTokenMetadata($user->id, $refreshPayload['jti'], $refreshExpiresAt);
        
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => self::TOKEN_TYPE,
            'expires_in' => $expiresAt - $issuedAt,
            'tenant_id' => $payload['tenant_id'],
        ];
    }
    
    /**
     * Encode JWT token
     */
    private function encodeToken(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];
        
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . '.' . $base64UrlPayload,
            $this->getSecret(),
            true
        );
        
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
    
    /**
     * Decode and validate JWT token
     */
    public function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;
        
        // Verify signature
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . '.' . $base64UrlPayload,
            $this->getSecret(),
            true
        );
        
        $expectedSignature = $this->base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $base64UrlSignature)) {
            return null; // Invalid signature
        }
        
        // Decode payload
        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);
        
        if (!$payload) {
            return null;
        }
        
        // Verify expiration
        if (isset($payload['exp']) && $payload['exp'] < now()->timestamp) {
            return null; // Token expired
        }
        
        // Check if token is blacklisted
        if ($this->isTokenBlacklisted($payload['jti'] ?? '')) {
            return null; // Token revoked
        }
        
        return $payload;
    }
    
    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $payload = $this->decodeToken($refreshToken);
        
        if (!$payload || ($payload['type'] ?? 'access') !== 'refresh') {
            return null;
        }
        
        $user = User::find($payload['sub']);
        
        if (!$user || !$user->is_active) {
            return null;
        }
        
        // Generate new access token
        return $this->generateToken($user);
    }
    
    /**
     * Revoke token (blacklist)
     */
    public function revokeToken(string $token): bool
    {
        $payload = $this->decodeToken($token);
        
        if (!$payload) {
            return false;
        }
        
        $jti = $payload['jti'] ?? '';
        $ttl = ($payload['exp'] ?? now()->timestamp) - now()->timestamp;
        
        if ($ttl > 0 && $jti) {
            Cache::put("jwt:blacklist:{$jti}", true, $ttl);
            $this->removeTokenMetadata($payload['sub'] ?? null, $jti);
            return true;
        }
        
        return false;
    }
    
    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): void
    {
        $tokenIds = Cache::get("jwt:user:{$userId}:tokens", []);
        
        foreach ($tokenIds as $jti => $expiry) {
            if ($expiry > now()->timestamp) {
                Cache::put("jwt:blacklist:{$jti}", true, $expiry - now()->timestamp);
            }
        }
        
        Cache::forget("jwt:user:{$userId}:tokens");
    }
    
    /**
     * Check if token is blacklisted
     */
    private function isTokenBlacklisted(string $jti): bool
    {
        return Cache::has("jwt:blacklist:{$jti}");
    }
    
    /**
     * Store token metadata for tracking
     */
    private function storeTokenMetadata(int $userId, string $jti, int $expiresAt): void
    {
        $tokens = Cache::get("jwt:user:{$userId}:tokens", []);
        $tokens[$jti] = $expiresAt;
        
        // Store with TTL matching the longest token expiry
        $maxTtl = max(array_values($tokens)) - now()->timestamp;
        Cache::put("jwt:user:{$userId}:tokens", $tokens, max(0, $maxTtl));
    }
    
    /**
     * Remove token metadata
     */
    private function removeTokenMetadata(?int $userId, string $jti): void
    {
        if (!$userId) {
            return;
        }
        
        $tokens = Cache::get("jwt:user:{$userId}:tokens", []);
        unset($tokens[$jti]);
        
        if (empty($tokens)) {
            Cache::forget("jwt:user:{$userId}:tokens");
        } else {
            $maxTtl = max(array_values($tokens)) - now()->timestamp;
            Cache::put("jwt:user:{$userId}:tokens", $tokens, max(0, $maxTtl));
        }
    }
    
    /**
     * Get JWT secret from config
     */
    private function getSecret(): string
    {
        return config('iam.jwt.secret') ?? config('app.key');
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
