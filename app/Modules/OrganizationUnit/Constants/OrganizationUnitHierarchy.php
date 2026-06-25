<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Constants;

final class OrganizationUnitHierarchy
{
    public const ROOT_MARKER = 'root';
    public const DEFAULT_MAXIMUM_PATH_LENGTH = 1024;
    public const MINIMUM_PATH_LENGTH = 64;

    /** @return list<string> */
    public static function rootMarkerValues(): array
    {
        return [self::ROOT_MARKER];
    }

    private function __construct() {}
}
