<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Support;

use InvalidArgumentException;

final class OrganizationUnitNameKey
{
    public static function from(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            throw new InvalidArgumentException('A non-empty name is required to build a uniqueness key.');
        }

        return hash('sha256', $normalized);
    }

    private function __construct() {}
}
