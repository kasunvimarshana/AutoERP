<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Constants;

final class OrganizationUnitErrorCode
{
    public const NOT_FOUND = 'ORGANIZATION_UNIT_NOT_FOUND';
    public const TENANT_NOT_FOUND = 'ORGANIZATION_UNIT_TENANT_NOT_FOUND';
    public const TENANT_MISMATCH = 'ORGANIZATION_UNIT_TENANT_MISMATCH';
    public const CONFLICT = 'ORGANIZATION_UNIT_CONFLICT';
    public const INVALID_VALUE = 'ORGANIZATION_UNIT_INVALID_VALUE';

    private function __construct()
    {
    }
}