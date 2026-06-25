<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class AuthTokenKeyPrefix
{
    public const TENANT_ACCESS = 'ta_';
    public const PLATFORM_ACCESS = 'pa_';
    public const TENANT_REFRESH = 'tr_';
    public const PLATFORM_REFRESH = 'pr_';

    public static function accessForScope(string $scope): string
    {
        return AuthTokenScope::normalize($scope) === AuthTokenScope::PLATFORM
            ? self::PLATFORM_ACCESS
            : self::TENANT_ACCESS;
    }

    public static function refreshForScope(string $scope): string
    {
        return AuthTokenScope::normalize($scope) === AuthTokenScope::PLATFORM
            ? self::PLATFORM_REFRESH
            : self::TENANT_REFRESH;
    }

    public static function scopeFromAccessKey(string $key): ?string
    {
        return match (true) {
            str_starts_with($key, self::TENANT_ACCESS) => AuthTokenScope::TENANT,
            str_starts_with($key, self::PLATFORM_ACCESS) => AuthTokenScope::PLATFORM,
            default => null,
        };
    }

    public static function scopeFromRefreshKey(string $key): ?string
    {
        return match (true) {
            str_starts_with($key, self::TENANT_REFRESH) => AuthTokenScope::TENANT,
            str_starts_with($key, self::PLATFORM_REFRESH) => AuthTokenScope::PLATFORM,
            default => null,
        };
    }

    private function __construct() {}
}
