<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class RevocationService
{
    private const PREFIX = 'revoked_token:';
    private const USER_TOKENS_PREFIX = 'user_tokens:';
    private const DEFAULT_TTL = 900; // 15 minutes (match access token TTL)

    public function revokeToken(string $jti, int $ttlSeconds = self::DEFAULT_TTL): void
    {
        Cache::put(self::PREFIX . $jti, '1', $ttlSeconds);
    }

    public function isRevoked(string $jti): bool
    {
        return Cache::has(self::PREFIX . $jti);
    }

    public function revokeAllUserTokens(int $userId): void
    {
        Cache::put(
            self::USER_TOKENS_PREFIX . $userId,
            (string) now()->timestamp,
            86400 // 24 hours
        );
    }

    public function revokeUserDeviceTokens(int $userId, string $deviceId): void
    {
        Cache::put(
            "user_device_revoked:{$userId}:{$deviceId}",
            (string) now()->timestamp,
            86400
        );
    }

    public function getUserRevocationTimestamp(int $userId): ?int
    {
        $value = Cache::get(self::USER_TOKENS_PREFIX . $userId);
        return $value !== null ? (int) $value : null;
    }

    public function getDeviceRevocationTimestamp(int $userId, string $deviceId): ?int
    {
        $value = Cache::get("user_device_revoked:{$userId}:{$deviceId}");
        return $value !== null ? (int) $value : null;
    }
}
