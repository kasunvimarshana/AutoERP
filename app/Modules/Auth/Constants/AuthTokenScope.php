<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

use InvalidArgumentException;

final class AuthTokenScope
{
    public const TENANT = 'tenant';
    public const PLATFORM = 'platform';

    public static function normalize(string $scope): string
    {
        $scope = strtolower(trim($scope));
        if (! in_array($scope, [self::TENANT, self::PLATFORM], true)) {
            throw new InvalidArgumentException('Authentication token scope is invalid.');
        }

        return $scope;
    }

    private function __construct() {}
}
