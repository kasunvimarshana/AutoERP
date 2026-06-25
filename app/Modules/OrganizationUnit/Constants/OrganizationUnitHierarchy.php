<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Constants;

final class OrganizationUnitHierarchy
{
    public const ROOT_MARKER = 'root';

    /** @return list<string> */
    public static function rootMarkerValues(): array
    {
        return [self::ROOT_MARKER];
    }

    private function __construct() {}
}
